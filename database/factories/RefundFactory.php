<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'reason' => $this->faker->randomElement([
                'Customer request',
                'Product defect',
                'Wrong item shipped',
                'Item not as described',
                'Damaged in transit',
                'Order cancelled',
            ]),
            'status' => 'completed',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the refund is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the refund is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the refund is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'notes' => $this->faker->randomElement([
                'Refund policy expired',
                'Product used or damaged by customer',
                'Missing original packaging',
                'No valid reason provided',
            ]),
        ]);
    }

    /**
     * Create a full refund.
     */
    public function full(): static
    {
        return $this->state(function (array $attributes) {
            // Amount will match the order total
            return [
                'reason' => 'Full order refund',
            ];
        });
    }

    /**
     * Create a partial refund.
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'amount' => $this->faker->randomFloat(2, 5, 100),
                'reason' => 'Partial refund - ' . $this->faker->randomElement([
                    'One item returned',
                    'Damaged item',
                    'Price adjustment',
                ]),
            ];
        });
    }

    /**
     * Create a refund for a defective product.
     */
    public function defectiveProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'Product defect',
            'notes' => 'Product was defective upon arrival',
        ]);
    }

    /**
     * Create a refund for customer request.
     */
    public function customerRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'Customer request',
            'notes' => 'Customer changed their mind',
        ]);
    }
}