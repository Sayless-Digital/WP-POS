<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $method = $this->faker->randomElement(['cash', 'card', 'mobile_money', 'bank_transfer', 'other']);
        
        return [
            'order_id' => Order::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'method' => $method,
            'reference' => $this->generateReference($method),
            'status' => 'completed',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Generate a payment reference based on method.
     */
    protected function generateReference(string $method): string
    {
        return match($method) {
            'cash' => 'CASH-' . strtoupper($this->faker->bothify('####??')),
            'card' => 'CARD-' . strtoupper($this->faker->bothify('############')),
            'mobile_money' => 'MM-' . strtoupper($this->faker->bothify('##########')),
            'bank_transfer' => 'BT-' . strtoupper($this->faker->bothify('############')),
            'other' => 'PAY-' . strtoupper($this->faker->bothify('########')),
            default => 'REF-' . strtoupper($this->faker->bothify('########')),
        };
    }

    /**
     * Create a cash payment.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'cash',
            'reference' => 'CASH-' . strtoupper($this->faker->bothify('####??')),
        ]);
    }

    /**
     * Create a card payment.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'card',
            'reference' => 'CARD-' . strtoupper($this->faker->bothify('############')),
        ]);
    }

    /**
     * Create a mobile money payment.
     */
    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'mobile_money',
            'reference' => 'MM-' . strtoupper($this->faker->bothify('##########')),
        ]);
    }

    /**
     * Create a bank transfer payment.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'bank_transfer',
            'reference' => 'BT-' . strtoupper($this->faker->bothify('############')),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'notes' => $this->faker->randomElement([
                'Insufficient funds',
                'Card declined',
                'Transaction timeout',
                'Payment gateway error',
            ]),
        ]);
    }

    /**
     * Indicate that the payment is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
        ]);
    }
}