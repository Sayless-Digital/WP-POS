<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\SyncQueue;
use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckoutService
{
    protected CartService $cartService;
    protected InventoryService $inventoryService;
    protected OrderService $orderService;

    public function __construct(
        CartService $cartService,
        InventoryService $inventoryService,
        OrderService $orderService
    ) {
        $this->cartService = $cartService;
        $this->inventoryService = $inventoryService;
        $this->orderService = $orderService;
    }

    /**
     * Process checkout and create order
     *
     * @param array $cart
     * @param array $payments
     * @param Customer|null $customer
     * @param array $options
     * @return Order
     * @throws \Exception
     */
    public function processCheckout(
        array $cart,
        array $payments,
        ?Customer $customer = null,
        array $options = []
    ): Order {
        DB::beginTransaction();

        try {
            // Validate cart
            $validation = $this->cartService->validateCart($cart);
            if (!$validation['valid']) {
                throw new \Exception("Cart validation failed: " . implode(', ', $validation['errors']));
            }

            // Validate payments
            $cartTotal = $this->cartService->calculateTotal($cart, $options['cart_discount'] ?? 0);
            $totalPaid = collect($payments)->sum('amount');

            if ($totalPaid < $cartTotal) {
                throw new \Exception("Insufficient payment. Required: {$cartTotal}, Paid: {$totalPaid}");
            }

            // Calculate totals
            $subtotal = $this->cartService->calculateSubtotal($cart);
            $itemDiscounts = $this->cartService->calculateDiscount($cart);
            $cartDiscount = $options['cart_discount'] ?? 0;
            $taxAmount = $this->cartService->calculateTax($cart);

            // Create order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer?->id,
                'user_id' => Auth::id(),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $itemDiscounts + $cartDiscount,
                'total' => $cartTotal,
                'payment_status' => 'paid',
                'notes' => $options['notes'] ?? null,
            ]);

            // Create order items and update inventory
            foreach ($cart as $item) {
                $this->createOrderItem($order, $item);
            }

            // Create payment records
            foreach ($payments as $payment) {
                $this->createPayment($order, $payment);
            }

            // Update customer stats
            if ($customer) {
                $this->updateCustomerStats($customer, $order);
            }

            // Record cash drawer movement if applicable
            $this->recordCashDrawerMovement($order, $payments);

            // Queue for WooCommerce sync
            SyncQueue::create([
                'syncable_type' => Order::class,
                'syncable_id' => $order->id,
                'action' => 'create',
                'status' => 'pending',
            ]);

            DB::commit();

            return $order->load(['items', 'payments', 'customer']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create order item and update inventory
     *
     * @param Order $order
     * @param array $item
     * @return OrderItem
     */
    protected function createOrderItem(Order $order, array $item): OrderItem
    {
        // Create order item
        $orderItem = $order->items()->create([
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'] ?? null,
            'sku' => $item['sku'],
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'tax_rate' => $item['tax_rate'],
            'discount_amount' => $item['discount_amount'] ?? 0,
            'subtotal' => $item['price'] * $item['quantity'],
            'total' => $item['subtotal'],
        ]);

        // Update inventory
        if ($item['track_inventory']) {
            $product = $item['type'] === 'variant'
                ? \App\Models\ProductVariant::find($item['variant_id'])
                : \App\Models\Product::find($item['product_id']);

            if ($product) {
                $this->inventoryService->processSale($product, $item['quantity'], [
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'notes' => "Sale - Order #{$order->order_number}",
                ]);
            }
        }

        return $orderItem;
    }

    /**
     * Create payment record
     *
     * @param Order $order
     * @param array $payment
     * @return Payment
     */
    protected function createPayment(Order $order, array $payment): Payment
    {
        return $order->payments()->create([
            'payment_method' => $payment['method'],
            'amount' => $payment['amount'],
            'reference' => $payment['reference'] ?? null,
            'notes' => $payment['notes'] ?? null,
        ]);
    }

    /**
     * Update customer statistics
     *
     * @param Customer $customer
     * @param Order $order
     * @return void
     */
    protected function updateCustomerStats(Customer $customer, Order $order): void
    {
        $customer->increment('total_spent', $order->total);
        $customer->increment('total_orders');

        // Award loyalty points if applicable
        if (config('pos.enable_loyalty_points', false)) {
            $points = floor($order->total * config('pos.loyalty_points_rate', 0.01));
            $customer->increment('loyalty_points', $points);
        }
    }

    /**
     * Record cash drawer movement
     *
     * @param Order $order
     * @param array $payments
     * @return void
     */
    protected function recordCashDrawerMovement(Order $order, array $payments): void
    {
        // Get active cash drawer session for current user
        $session = CashDrawerSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$session) {
            return;
        }

        // Record cash payments
        foreach ($payments as $payment) {
            if ($payment['method'] === 'cash') {
                CashMovement::create([
                    'session_id' => $session->id,
                    'type' => 'sale',
                    'amount' => $payment['amount'],
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'notes' => "Sale - Order #{$order->order_number}",
                ]);
            }
        }
    }

    /**
     * Generate unique order number
     *
     * @return string
     */
    protected function generateOrderNumber(): string
    {
        $prefix = config('pos.order_number_prefix', 'POS-');
        $date = now()->format('Ymd');
        
        // Get count of orders today
        $count = Order::whereDate('created_at', today())->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        $orderNumber = $prefix . $date . '-' . $sequence;

        // Ensure uniqueness
        while (Order::where('order_number', $orderNumber)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $orderNumber = $prefix . $date . '-' . $sequence;
        }

        return $orderNumber;
    }

    /**
     * Calculate change to return
     *
     * @param float $total
     * @param float $tendered
     * @return float
     */
    public function calculateChange(float $total, float $tendered): float
    {
        return max(0, $tendered - $total);
    }

    /**
     * Validate payment amount
     *
     * @param array $payments
     * @param float $total
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validatePayments(array $payments, float $total): array
    {
        if (empty($payments)) {
            return ['valid' => false, 'message' => 'No payment method provided'];
        }

        $totalPaid = collect($payments)->sum('amount');

        if ($totalPaid < $total) {
            $remaining = $total - $totalPaid;
            return [
                'valid' => false,
                'message' => "Insufficient payment. Remaining: " . number_format($remaining, 2),
            ];
        }

        foreach ($payments as $payment) {
            if (empty($payment['method'])) {
                return ['valid' => false, 'message' => 'Payment method is required'];
            }

            if ($payment['amount'] <= 0) {
                return ['valid' => false, 'message' => 'Payment amount must be greater than zero'];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Split payment calculation
     *
     * @param float $total
     * @param array $payments Existing payments
     * @return float Remaining amount
     */
    public function calculateRemainingAmount(float $total, array $payments): float
    {
        $paid = collect($payments)->sum('amount');
        return max(0, $total - $paid);
    }

    /**
     * Process quick cash payment
     *
     * @param array $cart
     * @param float $cashTendered
     * @param Customer|null $customer
     * @param array $options
     * @return array ['order' => Order, 'change' => float]
     * @throws \Exception
     */
    public function processQuickCashPayment(
        array $cart,
        float $cashTendered,
        ?Customer $customer = null,
        array $options = []
    ): array {
        $total = $this->cartService->calculateTotal($cart, $options['cart_discount'] ?? 0);

        if ($cashTendered < $total) {
            throw new \Exception("Insufficient cash. Required: {$total}, Tendered: {$cashTendered}");
        }

        $payments = [
            [
                'method' => 'cash',
                'amount' => $total,
            ]
        ];

        $order = $this->processCheckout($cart, $payments, $customer, $options);
        $change = $this->calculateChange($total, $cashTendered);

        return [
            'order' => $order,
            'change' => $change,
        ];
    }

    /**
     * Hold/park an order for later
     *
     * @param array $cart
     * @param Customer|null $customer
     * @param string|null $notes
     * @return \App\Models\HeldOrder
     */
    public function holdOrder(array $cart, ?Customer $customer = null, ?string $notes = null)
    {
        return \App\Models\HeldOrder::create([
            'user_id' => Auth::id(),
            'customer_id' => $customer?->id,
            'cart_data' => json_encode($cart),
            'notes' => $notes,
        ]);
    }

    /**
     * Resume a held order
     *
     * @param int $heldOrderId
     * @return array ['cart' => array, 'customer' => Customer|null]
     */
    public function resumeHeldOrder(int $heldOrderId): array
    {
        $heldOrder = \App\Models\HeldOrder::findOrFail($heldOrderId);
        
        $cart = json_decode($heldOrder->cart_data, true);
        $customer = $heldOrder->customer;

        // Delete the held order
        $heldOrder->delete();

        return [
            'cart' => $cart,
            'customer' => $customer,
        ];
    }

    /**
     * Get checkout summary
     *
     * @param array $cart
     * @param array $payments
     * @param float $cartDiscount
     * @return array
     */
    public function getCheckoutSummary(array $cart, array $payments, float $cartDiscount = 0): array
    {
        $cartSummary = $this->cartService->getCartSummary($cart, $cartDiscount);
        $totalPaid = collect($payments)->sum('amount');
        $change = $this->calculateChange($cartSummary['total'], $totalPaid);

        return array_merge($cartSummary, [
            'total_paid' => $totalPaid,
            'change' => $change,
            'payment_methods' => collect($payments)->pluck('method')->unique()->values()->toArray(),
        ]);
    }
}