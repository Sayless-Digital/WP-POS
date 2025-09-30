<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 5, 500);
        $taxRate = $this->faker->randomElement([0, 5, 10, 12.5, 15]);
        $discount = $this->faker->optional(0.2)->randomFloat(2, 0, $unitPrice * 0.2);
        
        $subtotal = $quantity * $unitPrice;
        $discountAmount = $discount ? $quantity * $discount : 0;
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $total = $subtotal - $discountAmount + $taxAmount;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'product_name' => fn (array $attributes) => 'Product ' . $this->faker->word(),
            'sku' => strtoupper($this->faker->bothify('SKU-####??')),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'total' => $total,
        ];
    }

    /**
     * Indicate that the item is for a product variant.
     */
    public function forVariant($variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => $variant?->id ?? ProductVariant::factory(),
        ]);
    }

    /**
     * Indicate that the item has a discount.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $unitPrice = $attributes['unit_price'];
            $discount = $this->faker->randomFloat(2, $unitPrice * 0.1, $unitPrice * 0.3);
            $taxRate = $attributes['tax_rate'];
            
            $subtotal = $quantity * $unitPrice;
            $discountAmount = $quantity * $discount;
            $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
            $total = $subtotal - $discountAmount + $taxAmount;
            
            return [
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ];
        });
    }

    /**
     * Indicate that the item has no tax.
     */
    public function noTax(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $unitPrice = $attributes['unit_price'];
            $discountAmount = $attributes['discount_amount'];
            
            $subtotal = $quantity * $unitPrice;
            $total = $subtotal - $discountAmount;
            
            return [
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $total,
            ];
        });
    }

    /**
     * Create a bulk order item with high quantity.
     */
    public function bulk(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->numberBetween(50, 200);
            $unitPrice = $attributes['unit_price'];
            $discountAmount = $attributes['discount_amount'];
            $taxRate = $attributes['tax_rate'];
            
            $subtotal = $quantity * $unitPrice;
            $totalDiscount = $quantity * ($discountAmount / $attributes['quantity']);
            $taxAmount = ($subtotal - $totalDiscount) * ($taxRate / 100);
            $total = $subtotal - $totalDiscount + $taxAmount;
            
            return [
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ];
        });
    }
}