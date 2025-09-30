<?php

namespace App\Livewire\Pos;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\ProductService;
use App\Services\InventoryService;
use App\Services\DiscountService;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class PosTerminal extends Component
{
    // Cart state
    public array $cart = [];
    public float $cartDiscount = 0;
    public string $discountType = 'fixed'; // 'fixed' or 'percentage'
    
    // Customer
    public ?int $customerId = null;
    
    // Search
    public string $searchQuery = '';
    public array $searchResults = [];
    
    // UI state
    public bool $showCustomerModal = false;
    public bool $showDiscountModal = false;
    public bool $showHeldOrdersModal = false;
    public string $notes = '';
    
    // Services
    protected CartService $cartService;
    protected ProductService $productService;
    protected InventoryService $inventoryService;
    protected DiscountService $discountService;
    
    public function boot(
        CartService $cartService,
        ProductService $productService,
        InventoryService $inventoryService,
        DiscountService $discountService
    ) {
        $this->cartService = $cartService;
        $this->productService = $productService;
        $this->inventoryService = $inventoryService;
        $this->discountService = $discountService;
    }
    
    public function mount()
    {
        // Initialize empty cart
        $this->cart = [];
        $this->loadCartFromSession();
    }
    
    /**
     * Load cart from session (for persistence)
     */
    protected function loadCartFromSession()
    {
        $sessionCart = session('pos_cart', []);
        if (!empty($sessionCart)) {
            $this->cart = $sessionCart['items'] ?? [];
            $this->cartDiscount = $sessionCart['discount'] ?? 0;
            $this->discountType = $sessionCart['discount_type'] ?? 'fixed';
            $this->customerId = $sessionCart['customer_id'] ?? null;
            $this->notes = $sessionCart['notes'] ?? '';
        }
    }
    
    /**
     * Save cart to session
     */
    protected function saveCartToSession()
    {
        session([
            'pos_cart' => [
                'items' => $this->cart,
                'discount' => $this->cartDiscount,
                'discount_type' => $this->discountType,
                'customer_id' => $this->customerId,
                'notes' => $this->notes,
            ]
        ]);
    }
    
    /**
     * Search products by query or barcode
     */
    public function updatedSearchQuery()
    {
        if (empty($this->searchQuery)) {
            $this->searchResults = [];
            return;
        }
        
        // Check if it's a barcode (numeric and length > 8)
        if (is_numeric($this->searchQuery) && strlen($this->searchQuery) >= 8) {
            $item = $this->productService->findByBarcode($this->searchQuery);
            if ($item) {
                $this->addToCart($item);
                $this->searchQuery = '';
                $this->searchResults = [];
                return;
            }
        }
        
        // Regular search
        $this->searchResults = $this->productService->searchProducts($this->searchQuery, [
            'is_active' => true,
            'limit' => 10
        ])->toArray();
    }
    
    /**
     * Listen for barcode scanned event
     */
    #[On('barcode-scanned')]
    public function handleBarcodeScanned($barcode)
    {
        $item = $this->productService->findByBarcode($barcode);
        
        if ($item) {
            $this->addToCart($item);
            $this->dispatch('barcode-success', message: 'Product added to cart');
        } else {
            $this->dispatch('barcode-error', message: 'Product not found');
        }
    }
    
    /**
     * Add item to cart
     */
    public function addToCart($item, int $quantity = 1)
    {
        // Check stock availability
        if (!$this->inventoryService->checkAvailability($item, $quantity)) {
            $this->dispatch('error', message: 'Insufficient stock available');
            return;
        }
        
        try {
            $this->cart = $this->cartService->addItem($this->cart, $item, $quantity);
            $this->saveCartToSession();
            $this->dispatch('item-added', message: 'Item added to cart');
            
            // Clear search
            $this->searchQuery = '';
            $this->searchResults = [];
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart(int $index)
    {
        try {
            $this->cart = $this->cartService->removeItem($this->cart, $index);
            $this->saveCartToSession();
            $this->dispatch('item-removed', message: 'Item removed from cart');
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }
    
    /**
     * Update item quantity
     */
    public function updateQuantity(int $index, int $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);
            return;
        }
        
        try {
            // Check stock availability
            $item = $this->cart[$index];
            $itemModel = $item['variant_id'] 
                ? ProductVariant::find($item['variant_id'])
                : Product::find($item['product_id']);
                
            if (!$this->inventoryService->checkAvailability($itemModel, $quantity)) {
                $this->dispatch('error', message: 'Insufficient stock available');
                return;
            }
            
            $this->cart = $this->cartService->updateQuantity($this->cart, $index, $quantity);
            $this->saveCartToSession();
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }
    
    /**
     * Apply discount to specific item
     */
    public function applyItemDiscount(int $index, float $discount, string $type = 'fixed')
    {
        try {
            $this->cart = $this->cartService->applyItemDiscount($this->cart, $index, $discount, $type);
            $this->saveCartToSession();
            $this->dispatch('discount-applied', message: 'Discount applied');
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }
    
    /**
     * Open discount modal
     */
    public function openDiscountModal()
    {
        $summary = $this->cartSummary;
        $this->showDiscountModal = true;
        $this->dispatch('open-discount-modal',
            cartSubtotal: $summary['subtotal'],
            currentDiscount: $this->cartDiscount,
            discountType: $this->discountType
        );
    }
    
    /**
     * Listen for discount applied event
     */
    #[On('discount-applied')]
    public function applyCartDiscount(array $data)
    {
        $this->cartDiscount = $data['amount'];
        $this->discountType = $data['type'];
        $this->saveCartToSession();
        $this->showDiscountModal = false;
        $this->dispatch('success', message: 'Cart discount applied');
    }
    
    /**
     * Listen for discount removed event
     */
    #[On('discount-removed')]
    public function removeCartDiscount()
    {
        $this->cartDiscount = 0;
        $this->discountType = 'fixed';
        $this->saveCartToSession();
        $this->showDiscountModal = false;
        $this->dispatch('success', message: 'Discount removed');
    }
    
    /**
     * Open customer modal
     */
    public function openCustomerModal()
    {
        $this->showCustomerModal = true;
        $this->dispatch('open-customer-search-modal');
    }
    
    /**
     * Open held orders modal
     */
    public function openHeldOrdersModal()
    {
        $this->showHeldOrdersModal = true;
        $this->dispatch('open-held-orders-modal');
    }
    
    /**
     * Listen for customer selected event
     */
    #[On('customer-selected')]
    public function selectCustomer(?int $customerId)
    {
        $this->customerId = $customerId;
        $this->saveCartToSession();
        $this->showCustomerModal = false;
        
        // Apply customer group discount if applicable
        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer && $customer->customerGroup) {
                $groupDiscount = $customer->customerGroup->discount_percentage;
                if ($groupDiscount > 0) {
                    $this->cartDiscount = $groupDiscount;
                    $this->discountType = 'percentage';
                    $this->saveCartToSession();
                    $this->dispatch('success', message: 'Customer group discount applied');
                }
            }
        }
    }
    
    /**
     * Hold current order
     */
    public function holdOrder()
    {
        if (empty($this->cart)) {
            $this->dispatch('error', message: 'Cart is empty');
            return;
        }
        
        try {
            // Calculate totals
            $summary = $this->cartSummary;
            
            $heldOrder = \App\Models\HeldOrder::create([
                'user_id' => auth()->id(),
                'customer_id' => $this->customerId,
                'items' => $this->cart,
                'subtotal' => $summary['subtotal'],
                'tax_amount' => $summary['tax'],
                'discount_amount' => $summary['discount'],
                'total' => $summary['total'],
                'notes' => $this->notes,
            ]);
            
            $this->clearCart();
            $this->dispatch('order-held', message: 'Order held successfully', reference: $heldOrder->reference);
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to hold order: ' . $e->getMessage());
        }
    }
    
    /**
     * Resume held order
     */
    #[On('resume-held-order')]
    public function resumeHeldOrder(int $heldOrderId)
    {
        try {
            $heldOrder = \App\Models\HeldOrder::findOrFail($heldOrderId);
            
            $this->cart = $heldOrder->items;
            $this->cartDiscount = $heldOrder->discount_amount;
            $this->discountType = 'fixed';
            $this->customerId = $heldOrder->customer_id;
            $this->notes = $heldOrder->notes ?? '';
            
            $this->saveCartToSession();
            
            // Delete held order
            $heldOrder->delete();
            
            $this->showHeldOrdersModal = false;
            $this->dispatch('order-resumed', message: 'Order resumed successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to resume order: ' . $e->getMessage());
        }
    }
    
    /**
     * Clear cart
     */
    public function clearCart()
    {
        $this->cart = [];
        $this->cartDiscount = 0;
        $this->discountType = 'fixed';
        $this->customerId = null;
        $this->notes = '';
        $this->searchQuery = '';
        $this->searchResults = [];
        
        session()->forget('pos_cart');
        
        $this->dispatch('cart-cleared', message: 'Cart cleared');
    }
    
    /**
     * Proceed to checkout
     */
    public function proceedToCheckout()
    {
        if (empty($this->cart)) {
            $this->dispatch('error', message: 'Cart is empty');
            return;
        }
        
        // Validate cart
        $validation = $this->cartService->validateCart($this->cart);
        if (!$validation['valid']) {
            $this->dispatch('error', message: $validation['message']);
            return;
        }
        
        // Save cart and redirect to checkout
        $this->saveCartToSession();
        return redirect()->route('pos.checkout');
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
        $discount = $this->discountService->calculateDiscount($subtotal, $this->cartDiscount, $this->discountType);
        $tax = $this->cartService->calculateTax($this->cart);
        $total = $this->cartService->calculateTotal($this->cart, $this->cartDiscount);
        
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
    
    public function render()
    {
        return view('livewire.pos.pos-terminal');
    }
}