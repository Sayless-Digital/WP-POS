<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\HeldOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeldOrder>
 */
class HeldOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'HOLD-' . strtoupper($this->faker->unique()->bothify('####??')),
            'customer_id' => $this->faker->optional(0.7)->randomElement([
                null,
                Customer::factory(),
            ]),
            'user_id' => User::factory(),
            'items' => $this->generateItems(),
            'subtotal' => fn (array $attributes) => $this->calculateSubtotal($attributes['items']),
            'tax_amount' => fn (array $attributes) => $this->calculateTax($attributes['items']),
            'discount_amount' => fn (array $attributes) => $this->calculateDiscount($attributes['items']),
            'total' => fn (array $attributes) => $attributes['subtotal'] + $attributes['tax_amount'] - $attributes['discount_amount'],
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Generate random order items.
     */
    protected function generateItems(): array
    {
        $itemCount = $this->faker->numberBetween(1, 5);
        $items = [];

        for ($i = 0; $i < $itemCount; $i++) {
            $quantity = $this->faker->numberBetween(1, 5);
            $unitPrice = $this->faker->randomFloat(2, 5, 200);
            $taxRate = $this->faker->randomElement([0, 5, 10, 12.5, 15]);
            $discount = $this->faker->optional(0.2)->randomFloat(2, 0, $unitPrice * 0.2) ?? 0;

            $items[] = [
                'product_id' => $this->faker->numberBetween(1, 100),
                'product_variant_id' => $this->faker->optional(0.3)->numberBetween(1, 50),
                'product_name' => $this->faker->words(3, true),
                'sku' => strtoupper($this->faker->bothify('SKU-####??')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount * $quantity,
                'tax_rate' => $taxRate,
            ];
        }

        return $items;
    }

    /**
     * Calculate subtotal from items.
     */
    protected function calculateSubtotal(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            return $carry + ($item['quantity'] * $item['unit_price']);
        }, 0);
    }

    /**
     * Calculate tax from items.
     */
    protected function calculateTax(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $afterDiscount = $subtotal - $item['discount_amount'];
            return $carry + ($afterDiscount * ($item['tax_rate'] / 100));
        }, 0);
    }

    /**
     * Calculate discount from items.
     */
    protected function calculateDiscount(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            return $carry + $item['discount_amount'];
        }, 0);
    }

    /**
     * Create a held order with a customer.
     */
    public function withCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    /**
     * Create a held order without a customer (walk-in).
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
        ]);
    }

    /**
     * Create a held order with many items.
     */
    public function withManyItems(): static
    {
        return $this->state(function (array $attributes) {
            $itemCount = $this->faker->numberBetween(10, 20);
            $items = [];

            for ($i = 0; $i < $itemCount; $i++) {
                $quantity = $this->faker->numberBetween(1, 5);
                $unitPrice = $this->faker->randomFloat(2, 5, 200);
                $taxRate = $this->faker->randomElement([0, 5, 10, 12.5, 15]);
                $discount = $this->faker->optional(0.2)->randomFloat(2, 0, $unitPrice * 0.2) ?? 0;

                $items[] = [
                    'product_id' => $this->faker->numberBetween(1, 100),
                    'product_variant_id' => $this->faker->optional(0.3)->numberBetween(1, 50),
                    'product_name' => $this->faker->words(3, true),
                    'sku' => strtoupper($this->faker->bothify('SKU-####??')),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount * $quantity,
                    'tax_rate' => $taxRate,
                ];
            }

            return [
                'items' => $items,
                'subtotal' => $this->calculateSubtotal($items),
                'tax_amount' => $this->calculateTax($items),
                'discount_amount' => $this->calculateDiscount($items),
            ];
        });
    }
}