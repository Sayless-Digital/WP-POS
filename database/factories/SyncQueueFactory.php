<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SyncQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncQueue>
 */
class SyncQueueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'syncable_type' => Product::class,
            'syncable_id' => Product::factory(),
            'action' => $this->faker->randomElement(['create', 'update', 'delete']),
            'payload' => $this->generatePayload(),
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'scheduled_at' => now(),
        ];
    }

    /**
     * Generate a sample payload.
     */
    protected function generatePayload(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'sku' => strtoupper($this->faker->bothify('SKU-####??')),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Indicate that the sync is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
        ]);
    }

    /**
     * Indicate that the sync is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'attempts' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the sync is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'attempts' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the sync failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => $this->faker->randomElement([
                'Connection timeout',
                'API rate limit exceeded',
                'Invalid authentication',
                'Resource not found',
                'Server error',
            ]),
        ]);
    }

    /**
     * Create a sync for creating a record.
     */
    public function create(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'create',
        ]);
    }

    /**
     * Create a sync for updating a record.
     */
    public function update(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'update',
        ]);
    }

    /**
     * Create a sync for deleting a record.
     */
    public function delete(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'delete',
        ]);
    }

    /**
     * Create a sync for an Order.
     */
    public function forOrder($order = null): static
    {
        return $this->state(fn (array $attributes) => [
            'syncable_type' => \App\Models\Order::class,
            'syncable_id' => $order?->id ?? \App\Models\Order::factory(),
        ]);
    }

    /**
     * Create a sync for a Customer.
     */
    public function forCustomer($customer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'syncable_type' => \App\Models\Customer::class,
            'syncable_id' => $customer?->id ?? \App\Models\Customer::factory(),
        ]);
    }

    /**
     * Create a sync scheduled for later.
     */
    public function scheduledLater(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
        ]);
    }
}