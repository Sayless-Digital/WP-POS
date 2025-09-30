<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use Illuminate\Support\Collection;

class CartService
{
    protected InventoryService $inventoryService;
    protected DiscountService $discountService;

    public function __construct(InventoryService $inventoryService, DiscountService $discountService)
    {
        $this->inventoryService = $inventoryService;
        $this->discountService = $discountService;
    }

    /**
     * Add item to cart
     *
     * @param array $cart
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @return array
     * @throws \Exception
     */
    public function addItem(array $cart, $item, int $quantity = 1): array
    {
        // Check if item is active
        if (!$item->is_active) {
            throw new \Exception("Item is not available for sale");
        }

        // Check inventory
        if ($item->track_inventory && !$this->inventoryService->isInStock($item, $quantity)) {
            $available = $this->inventoryService->getAvailableStock($item);
            throw new \Exception("Insufficient stock. Available: {$available}");
        }

        // Get item key
        $itemKey = $this->getItemKey($item);

        // Check if item already exists in cart
        $existingIndex = $this->findItemInCart($cart, $itemKey);

        if ($existingIndex !== false) {
            // Update quantity
            $newQuantity = $cart[$existingIndex]['quantity'] + $quantity;
            
            // Check stock for new quantity
            if ($item->track_inventory && !$this->inventoryService->isInStock($item, $newQuantity)) {
                throw new \Exception("Insufficient stock for requested quantity");
            }
            
            $cart[$existingIndex]['quantity'] = $newQuantity;
            $cart[$existingIndex]['subtotal'] = $this->calculateItemSubtotal($cart[$existingIndex]);
        } else {
            // Add new item
            $cart[] = $this->createCartItem($item, $quantity);
        }

        return $cart;
    }

    /**
     * Remove item from cart
     *
     * @param array $cart
     * @param int $index
     * @return array
     */
    public function removeItem(array $cart, int $index): array
    {
        if (isset($cart[$index])) {
            unset($cart[$index]);
            $cart = array_values($cart); // Re-index array
        }

        return $cart;
    }

    /**
     * Update item quantity in cart
     *
     * @param array $cart
     * @param int $index
     * @param int $quantity
     * @return array
     * @throws \Exception
     */
    public function updateQuantity(array $cart, int $index, int $quantity): array
    {
        if (!isset($cart[$index])) {
            throw new \Exception("Item not found in cart");
        }

        if ($quantity <= 0) {
            return $this->removeItem($cart, $index);
        }

        $item = $cart[$index];

        // Check inventory if tracking
        if ($item['track_inventory']) {
            $product = $this->getItemFromCart($item);
            if (!$this->inventoryService->isInStock($product, $quantity)) {
                throw new \Exception("Insufficient stock for requested quantity");
            }
        }

        $cart[$index]['quantity'] = $quantity;
        $cart[$index]['subtotal'] = $this->calculateItemSubtotal($cart[$index]);

        return $cart;
    }

    /**
     * Apply discount to cart item
     *
     * @param array $cart
     * @param int $index
     * @param float $discountAmount
     * @param string $discountType 'fixed' or 'percentage'
     * @return array
     */
    public function applyItemDiscount(array $cart, int $index, float $discountAmount, string $discountType = 'fixed'): array
    {
        if (!isset($cart[$index])) {
            throw new \Exception("Item not found in cart");
        }

        $item = $cart[$index];
        $subtotal = $item['price'] * $item['quantity'];

        if ($discountType === 'percentage') {
            $discount = $subtotal * ($discountAmount / 100);
        } else {
            $discount = $discountAmount;
        }

        // Ensure discount doesn't exceed subtotal
        $discount = min($discount, $subtotal);

        $cart[$index]['discount_amount'] = $discount;
        $cart[$index]['discount_type'] = $discountType;
        $cart[$index]['subtotal'] = $subtotal - $discount;

        return $cart;
    }

    /**
     * Clear cart
     *
     * @return array
     */
    public function clearCart(): array
    {
        return [];
    }

    /**
     * Calculate cart subtotal
     *
     * @param array $cart
     * @return float
     */
    public function calculateSubtotal(array $cart): float
    {
        return collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    /**
     * Calculate cart tax
     *
     * @param array $cart
     * @return float
     */
    public function calculateTax(array $cart): float
    {
        return collect($cart)->sum(function ($item) {
            $subtotal = ($item['price'] * $item['quantity']) - ($item['discount_amount'] ?? 0);
            return $subtotal * ($item['tax_rate'] / 100);
        });
    }

    /**
     * Calculate cart discount
     *
     * @param array $cart
     * @return float
     */
    public function calculateDiscount(array $cart): float
    {
        return collect($cart)->sum('discount_amount');
    }

    /**
     * Calculate cart total
     *
     * @param array $cart
     * @param float $cartDiscount Additional cart-level discount
     * @return float
     */
    public function calculateTotal(array $cart, float $cartDiscount = 0): float
    {
        $subtotal = $this->calculateSubtotal($cart);
        $itemDiscounts = $this->calculateDiscount($cart);
        $tax = $this->calculateTax($cart);

        return max(0, $subtotal - $itemDiscounts - $cartDiscount + $tax);
    }

    /**
     * Apply customer group discount
     *
     * @param array $cart
     * @param Customer|null $customer
     * @return array
     */
    public function applyCustomerDiscount(array $cart, ?Customer $customer): array
    {
        if (!$customer || !$customer->customerGroup) {
            return $cart;
        }

        $discountPercentage = $customer->customerGroup->discount_percentage;

        if ($discountPercentage > 0) {
            foreach ($cart as $index => $item) {
                if (!isset($item['discount_amount']) || $item['discount_amount'] == 0) {
                    $cart = $this->applyItemDiscount($cart, $index, $discountPercentage, 'percentage');
                }
            }
        }

        return $cart;
    }

    /**
     * Validate cart before checkout
     *
     * @param array $cart
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCart(array $cart): array
    {
        $errors = [];

        if (empty($cart)) {
            $errors[] = "Cart is empty";
            return ['valid' => false, 'errors' => $errors];
        }

        foreach ($cart as $index => $item) {
            $product = $this->getItemFromCart($item);

            if (!$product) {
                $errors[] = "Item at index {$index} not found";
                continue;
            }

            if (!$product->is_active) {
                $errors[] = "{$item['name']} is no longer available";
            }

            if ($product->track_inventory && !$this->inventoryService->isInStock($product, $item['quantity'])) {
                $available = $this->inventoryService->getAvailableStock($product);
                $errors[] = "Insufficient stock for {$item['name']}. Available: {$available}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get cart summary
     *
     * @param array $cart
     * @param float $cartDiscount
     * @return array
     */
    public function getCartSummary(array $cart, float $cartDiscount = 0): array
    {
        return [
            'item_count' => count($cart),
            'total_quantity' => collect($cart)->sum('quantity'),
            'subtotal' => $this->calculateSubtotal($cart),
            'discount' => $this->calculateDiscount($cart) + $cartDiscount,
            'tax' => $this->calculateTax($cart),
            'total' => $this->calculateTotal($cart, $cartDiscount),
        ];
    }

    /**
     * Create cart item array
     *
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @return array
     */
    protected function createCartItem($item, int $quantity): array
    {
        $isVariant = $item instanceof ProductVariant;

        return [
            'type' => $isVariant ? 'variant' : 'product',
            'product_id' => $isVariant ? $item->product_id : $item->id,
            'variant_id' => $isVariant ? $item->id : null,
            'sku' => $item->sku,
            'name' => $item->name,
            'price' => $item->price,
            'quantity' => $quantity,
            'tax_rate' => $item->tax_rate ?? ($isVariant ? $item->product->tax_rate : 0),
            'discount_amount' => 0,
            'discount_type' => null,
            'track_inventory' => $isVariant ? $item->product->track_inventory : $item->track_inventory,
            'subtotal' => $item->price * $quantity,
        ];
    }

    /**
     * Calculate item subtotal
     *
     * @param array $item
     * @return float
     */
    protected function calculateItemSubtotal(array $item): float
    {
        $subtotal = $item['price'] * $item['quantity'];
        return $subtotal - ($item['discount_amount'] ?? 0);
    }

    /**
     * Get item key for cart
     *
     * @param Product|ProductVariant $item
     * @return string
     */
    protected function getItemKey($item): string
    {
        if ($item instanceof ProductVariant) {
            return "variant_{$item->id}";
        }
        return "product_{$item->id}";
    }

    /**
     * Find item in cart by key
     *
     * @param array $cart
     * @param string $itemKey
     * @return int|false
     */
    protected function findItemInCart(array $cart, string $itemKey)
    {
        foreach ($cart as $index => $item) {
            $currentKey = $item['type'] === 'variant' 
                ? "variant_{$item['variant_id']}" 
                : "product_{$item['product_id']}";
            
            if ($currentKey === $itemKey) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Get product or variant from cart item
     *
     * @param array $item
     * @return Product|ProductVariant|null
     */
    protected function getItemFromCart(array $item)
    {
        if ($item['type'] === 'variant') {
            return ProductVariant::find($item['variant_id']);
        }
        return Product::find($item['product_id']);
    }
}