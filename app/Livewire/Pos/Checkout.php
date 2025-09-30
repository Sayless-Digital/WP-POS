<?php

namespace App\Livewire\Pos;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ReceiptService;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

class Checkout extends Component
{
    // Cart data from session
    public array $cart = [];
    public float $cartDiscount = 0;
    public string $discountType = 'fixed';
    public ?int $customerId = null;
    public string $notes = '';
    
    // Payment state
    public array $payments = [];
    public string $selectedPaymentMethod = 'cash';
    public float $cashTendered = 0;
    public float $changeAmount = 0;
    
    // UI state
    public bool $showPaymentModal = false;
    public bool $processing = false;
    public bool $orderCompleted = false;
    public ?int $completedOrderId = null;
    
    // Services
    protected CartService $cartService;
    protected OrderService $orderService;
    protected PaymentService $paymentService;
    protected ReceiptService $receiptService;
    protected InventoryService $inventoryService;
    
    public function boot(
        CartService $cartService,
        OrderService $orderService,
        PaymentService $paymentService,
        ReceiptService $receiptService,
        InventoryService $inventoryService
    ) {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->receiptService = $receiptService;
        $this->inventoryService = $inventoryService;
    }
    
    public function mount()
    {
        // Load cart from session
        $sessionCart = session('pos_cart', []);
        
        if (empty($sessionCart['items'])) {
            session()->flash('error', 'Cart is empty');
            return redirect()->route('pos.terminal');
        }
        
        $this->cart = $sessionCart['items'];
        $this->cartDiscount = $sessionCart['discount'] ?? 0;
        $this->discountType = $sessionCart['discount_type'] ?? 'fixed';
        $this->customerId = $sessionCart['customer_id'] ?? null;
        $this->notes = $sessionCart['notes'] ?? '';
        
        // Initialize cash tendered to total amount
        $this->cashTendered = $this->cartSummary['total'];
    }
    
    /**
     * Update cash tendered and calculate change
     */
    public function updatedCashTendered()
    {
        $this->calculateChange();
    }
    
    /**
     * Calculate change amount
     */
    protected function calculateChange()
    {
        $total = $this->cartSummary['total'];
        $totalPaid = $this->getTotalPaid();
        
        if ($this->selectedPaymentMethod === 'cash') {
            $this->changeAmount = max(0, ($totalPaid + $this->cashTendered) - $total);
        } else {
            $this->changeAmount = 0;
        }
    }
    
    /**
     * Get total amount already paid
     */
    protected function getTotalPaid(): float
    {
        return array_sum(array_column($this->payments, 'amount'));
    }
    
    /**
     * Add payment to split payment
     */
    public function addPayment()
    {
        $total = $this->cartSummary['total'];
        $totalPaid = $this->getTotalPaid();
        $remaining = $total - $totalPaid;
        
        if ($remaining <= 0) {
            $this->dispatch('error', message: 'Order is fully paid');
            return;
        }
        
        $amount = $this->selectedPaymentMethod === 'cash' 
            ? $this->cashTendered 
            : min($remaining, $this->cashTendered);
        
        if ($amount <= 0) {
            $this->dispatch('error', message: 'Invalid payment amount');
            return;
        }
        
        $this->payments[] = [
            'method' => $this->selectedPaymentMethod,
            'amount' => $amount,
            'reference' => null,
        ];
        
        // Reset for next payment
        $this->cashTendered = max(0, $remaining - $amount);
        $this->calculateChange();
        
        $this->dispatch('payment-added', message: 'Payment added');
    }
    
    /**
     * Remove payment from split payment
     */
    public function removePayment(int $index)
    {
        if (isset($this->payments[$index])) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
            $this->calculateChange();
            $this->dispatch('payment-removed', message: 'Payment removed');
        }
    }
    
    /**
     * Complete the order
     */
    public function completeOrder()
    {
        $this->processing = true;
        
        try {
            // Validate payment
            $total = $this->cartSummary['total'];
            $totalPaid = $this->getTotalPaid();
            
            // For single payment
            if (empty($this->payments)) {
                if ($this->selectedPaymentMethod === 'cash' && $this->cashTendered < $total) {
                    throw new \Exception('Insufficient cash tendered');
                }
                
                $this->payments[] = [
                    'method' => $this->selectedPaymentMethod,
                    'amount' => $this->selectedPaymentMethod === 'cash' ? $total : $this->cashTendered,
                    'reference' => null,
                ];
            }
            
            // Validate total payment
            $totalPaid = $this->getTotalPaid();
            if ($totalPaid < $total) {
                throw new \Exception('Insufficient payment. Remaining: $' . number_format($total - $totalPaid, 2));
            }
            
            DB::beginTransaction();
            
            // Create order
            $orderData = [
                'user_id' => auth()->id(),
                'customer_id' => $this->customerId,
                'subtotal' => $this->cartSummary['subtotal'],
                'discount' => $this->cartSummary['discount'],
                'tax' => $this->cartSummary['tax'],
                'total' => $total,
                'status' => 'completed',
                'notes' => $this->notes,
                'items' => $this->cart,
            ];
            
            $order = $this->orderService->createOrder($orderData);
            
            // Process payments
            foreach ($this->payments as $payment) {
                $this->paymentService->processPayment($order, [
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'],
                ]);
            }
            
            // Update inventory
            foreach ($this->cart as $item) {
                $this->inventoryService->decrementStock(
                    $item['product_id'],
                    $item['quantity'],
                    $item['variant_id'] ?? null
                );
            }
            
            DB::commit();
            
            // Clear cart session
            session()->forget('pos_cart');
            
            // Mark as completed
            $this->orderCompleted = true;
            $this->completedOrderId = $order->id;
            
            $this->dispatch('order-completed', orderId: $order->id);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->processing = false;
            $this->dispatch('error', message: 'Failed to complete order: ' . $e->getMessage());
        }
    }
    
    /**
     * Print receipt
     */
    public function printReceipt()
    {
        if (!$this->completedOrderId) {
            return;
        }
        
        try {
            $order = Order::with(['customer', 'items.product', 'payments'])->findOrFail($this->completedOrderId);
            $receipt = $this->receiptService->generateReceipt($order);
            
            // Trigger browser print
            $this->dispatch('print-receipt', receipt: $receipt);
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to print receipt: ' . $e->getMessage());
        }
    }
    
    /**
     * Email receipt
     */
    public function emailReceipt()
    {
        if (!$this->completedOrderId) {
            return;
        }
        
        try {
            $order = Order::with(['customer', 'items.product', 'payments'])->findOrFail($this->completedOrderId);
            
            if (!$order->customer || !$order->customer->email) {
                throw new \Exception('Customer email not available');
            }
            
            $this->receiptService->emailReceipt($order);
            $this->dispatch('success', message: 'Receipt sent to ' . $order->customer->email);
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to email receipt: ' . $e->getMessage());
        }
    }
    
    /**
     * Start new transaction
     */
    public function newTransaction()
    {
        return redirect()->route('pos.terminal');
    }
    
    /**
     * Cancel checkout and return to terminal
     */
    public function cancelCheckout()
    {
        return redirect()->route('pos.terminal');
    }
    
    /**
     * Computed property for cart summary
     */
    #[Computed]
    public function cartSummary()
    {
        if (empty($this->cart)) {
            return [
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => 0,
                'items_count' => 0,
            ];
        }
        
        $subtotal = $this->cartService->calculateSubtotal($this->cart);
        $discount = $this->cartService->calculateDiscount($subtotal, $this->cartDiscount, $this->discountType);
        $tax = $this->cartService->calculateTax($this->cart);
        $total = $subtotal - $discount + $tax;
        
        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'items_count' => count($this->cart),
        ];
    }
    
    /**
     * Computed property for selected customer
     */
    #[Computed]
    public function customer()
    {
        return $this->customerId ? Customer::find($this->customerId) : null;
    }
    
    /**
     * Computed property for remaining balance
     */
    #[Computed]
    public function remainingBalance()
    {
        return max(0, $this->cartSummary['total'] - $this->getTotalPaid());
    }
    
    public function render()
    {
        return view('livewire.pos.checkout');
    }
}