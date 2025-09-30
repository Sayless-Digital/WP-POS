<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10, 1000);
        $taxRate = $this->faker->randomElement([0, 5, 10, 12.5, 15]);
        $taxAmount = $subtotal * ($taxRate / 100);
        $discount = $this->faker->optional(0.3)->randomFloat(2, 0, $subtotal * 0.2);
        $total = $subtotal + $taxAmount - ($discount ?? 0);

        return [
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('####??##')),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount ?? 0,
            'total' => $total,
            'payment_status' => 'pending',
            'notes' => $this->faker->optional()->sentence(),
            'woocommerce_id' => null,
            'last_synced_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_status' => 'paid',
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'payment_status' => 'paid',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is partially paid.
     */
    public function partiallyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'partial',
        ]);
    }

    /**
     * Indicate that the order is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'refunded',
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the order is synced with WooCommerce.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'woocommerce_id' => $this->faker->unique()->numberBetween(1, 10000),
            'last_synced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the order has a discount.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $discount = $this->faker->randomFloat(2, $subtotal * 0.05, $subtotal * 0.25);
            $taxAmount = $attributes['tax_amount'];
            
            return [
                'discount_amount' => $discount,
                'total' => $subtotal + $taxAmount - $discount,
            ];
        });
    }

    /**
     * Create a walk-in customer order (no customer record).
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
        ]);
    }
}