<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;

class DiscountService
{
    /**
     * Calculate discount amount
     *
     * @param float $amount
     * @param float $discount
     * @param string $type 'fixed' or 'percentage'
     * @return float
     */
    public function calculateDiscount(float $amount, float $discount, string $type = 'fixed'): float
    {
        if ($type === 'percentage') {
            return $amount * ($discount / 100);
        }

        return min($discount, $amount); // Discount cannot exceed amount
    }

    /**
     * Apply discount to amount
     *
     * @param float $amount
     * @param float $discount
     * @param string $type
     * @return float
     */
    public function applyDiscount(float $amount, float $discount, string $type = 'fixed'): float
    {
        $discountAmount = $this->calculateDiscount($amount, $discount, $type);
        return max(0, $amount - $discountAmount);
    }

    /**
     * Get customer group discount
     *
     * @param Customer|null $customer
     * @return float Discount percentage
     */
    public function getCustomerGroupDiscount(?Customer $customer): float
    {
        if (!$customer || !$customer->customerGroup) {
            return 0;
        }

        return $customer->customerGroup->discount_percentage ?? 0;
    }

    /**
     * Calculate bulk discount based on quantity
     *
     * @param int $quantity
     * @param array $tiers Example: [['min' => 10, 'discount' => 5], ['min' => 20, 'discount' => 10]]
     * @return float Discount percentage
     */
    public function calculateBulkDiscount(int $quantity, array $tiers): float
    {
        $discount = 0;

        foreach ($tiers as $tier) {
            if ($quantity >= $tier['min']) {
                $discount = $tier['discount'];
            }
        }

        return $discount;
    }

    /**
     * Validate discount
     *
     * @param float $discount
     * @param string $type
     * @param float $amount
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateDiscount(float $discount, string $type, float $amount): array
    {
        if ($discount < 0) {
            return ['valid' => false, 'message' => 'Discount cannot be negative'];
        }

        if ($type === 'percentage' && $discount > 100) {
            return ['valid' => false, 'message' => 'Percentage discount cannot exceed 100%'];
        }

        if ($type === 'fixed' && $discount > $amount) {
            return ['valid' => false, 'message' => 'Fixed discount cannot exceed the amount'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Calculate promotional discount
     *
     * @param Product|ProductVariant $item
     * @param array $promotions
     * @return float
     */
    public function calculatePromotionalDiscount($item, array $promotions): float
    {
        $bestDiscount = 0;

        foreach ($promotions as $promo) {
            // Check if promotion applies to this item
            if ($this->promotionApplies($item, $promo)) {
                $discount = $this->calculateDiscount(
                    $item->price,
                    $promo['discount'],
                    $promo['type']
                );

                $bestDiscount = max($bestDiscount, $discount);
            }
        }

        return $bestDiscount;
    }

    /**
     * Check if promotion applies to item
     *
     * @param Product|ProductVariant $item
     * @param array $promotion
     * @return bool
     */
    protected function promotionApplies($item, array $promotion): bool
    {
        // Check date range
        $now = now();
        if (isset($promotion['start_date']) && $now->lt($promotion['start_date'])) {
            return false;
        }
        if (isset($promotion['end_date']) && $now->gt($promotion['end_date'])) {
            return false;
        }

        // Check if specific products
        if (isset($promotion['product_ids'])) {
            $productId = $item instanceof ProductVariant ? $item->product_id : $item->id;
            if (!in_array($productId, $promotion['product_ids'])) {
                return false;
            }
        }

        // Check if specific categories
        if (isset($promotion['category_ids'])) {
            $categoryId = $item instanceof ProductVariant 
                ? $item->product->category_id 
                : $item->category_id;
            
            if (!in_array($categoryId, $promotion['category_ids'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate cart-level discount
     *
     * @param float $subtotal
     * @param array $conditions Example: ['min_amount' => 100, 'discount' => 10, 'type' => 'fixed']
     * @return float
     */
    public function calculateCartDiscount(float $subtotal, array $conditions): float
    {
        // Check minimum amount requirement
        if (isset($conditions['min_amount']) && $subtotal < $conditions['min_amount']) {
            return 0;
        }

        return $this->calculateDiscount(
            $subtotal,
            $conditions['discount'],
            $conditions['type'] ?? 'fixed'
        );
    }

    /**
     * Apply coupon code
     *
     * @param string $code
     * @param float $amount
     * @param array $coupons Available coupons
     * @return array ['success' => bool, 'discount' => float, 'message' => string]
     */
    public function applyCoupon(string $code, float $amount, array $coupons): array
    {
        $coupon = collect($coupons)->firstWhere('code', strtoupper($code));

        if (!$coupon) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => 'Invalid coupon code',
            ];
        }

        // Check if coupon is active
        if (!($coupon['is_active'] ?? true)) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => 'Coupon is not active',
            ];
        }

        // Check date validity
        $now = now();
        if (isset($coupon['valid_from']) && $now->lt($coupon['valid_from'])) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => 'Coupon is not yet valid',
            ];
        }
        if (isset($coupon['valid_until']) && $now->gt($coupon['valid_until'])) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => 'Coupon has expired',
            ];
        }

        // Check minimum amount
        if (isset($coupon['min_amount']) && $amount < $coupon['min_amount']) {
            return [
                'success' => false,
                'discount' => 0,
                'message' => "Minimum amount of {$coupon['min_amount']} required",
            ];
        }

        // Check usage limit
        if (isset($coupon['usage_limit']) && isset($coupon['usage_count'])) {
            if ($coupon['usage_count'] >= $coupon['usage_limit']) {
                return [
                    'success' => false,
                    'discount' => 0,
                    'message' => 'Coupon usage limit reached',
                ];
            }
        }

        $discount = $this->calculateDiscount(
            $amount,
            $coupon['discount'],
            $coupon['type'] ?? 'fixed'
        );

        // Check maximum discount
        if (isset($coupon['max_discount'])) {
            $discount = min($discount, $coupon['max_discount']);
        }

        return [
            'success' => true,
            'discount' => $discount,
            'message' => 'Coupon applied successfully',
            'coupon' => $coupon,
        ];
    }

    /**
     * Calculate loyalty points discount
     *
     * @param int $points
     * @param float $pointValue Value of each point in currency
     * @param float $maxDiscount Maximum discount allowed
     * @return float
     */
    public function calculateLoyaltyDiscount(int $points, float $pointValue = 0.01, float $maxDiscount = null): float
    {
        $discount = $points * $pointValue;

        if ($maxDiscount !== null) {
            $discount = min($discount, $maxDiscount);
        }

        return $discount;
    }

    /**
     * Calculate employee discount
     *
     * @param float $amount
     * @param float $discountPercentage
     * @return float
     */
    public function calculateEmployeeDiscount(float $amount, float $discountPercentage = 10): float
    {
        return $this->calculateDiscount($amount, $discountPercentage, 'percentage');
    }

    /**
     * Get best applicable discount
     *
     * @param float $amount
     * @param array $discounts Array of discount options
     * @return array ['amount' => float, 'type' => string, 'source' => string]
     */
    public function getBestDiscount(float $amount, array $discounts): array
    {
        $bestDiscount = [
            'amount' => 0,
            'type' => null,
            'source' => null,
        ];

        foreach ($discounts as $discount) {
            $discountAmount = $this->calculateDiscount(
                $amount,
                $discount['value'],
                $discount['type']
            );

            if ($discountAmount > $bestDiscount['amount']) {
                $bestDiscount = [
                    'amount' => $discountAmount,
                    'type' => $discount['type'],
                    'source' => $discount['source'] ?? 'unknown',
                ];
            }
        }

        return $bestDiscount;
    }
}