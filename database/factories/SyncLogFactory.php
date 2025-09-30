<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncLog>
 */
class SyncLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['success', 'failed']);
        $duration = $this->faker->randomFloat(2, 0.1, 5.0);
        
        return [
            'syncable_type' => Product::class,
            'syncable_id' => Product::factory(),
            'action' => $this->faker->randomElement(['create', 'update', 'delete']),
            'status' => $status,
            'woocommerce_id' => $status === 'success' ? $this->faker->numberBetween(1, 10000) : null,
            'request_data' => $this->generateRequestData(),
            'response_data' => $status === 'success' ? $this->generateSuccessResponse() : null,
            'error_message' => $status === 'failed' ? $this->generateErrorMessage() : null,
            'duration' => $duration,
        ];
    }

    /**
     * Generate sample request data.
     */
    protected function generateRequestData(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'sku' => strtoupper($this->faker->bothify('SKU-####??')),
            'status' => 'publish',
        ];
    }

    /**
     * Generate sample success response.
     */
    protected function generateSuccessResponse(): array
    {
        return [
            'id' => $this->faker->numberBetween(1, 10000),
            'status' => 'success',
            'message' => 'Resource synced successfully',
        ];
    }

    /**
     * Generate sample error message.
     */
    protected function generateErrorMessage(): string
    {
        return $this->faker->randomElement([
            'Connection timeout',
            'API rate limit exceeded',
            'Invalid authentication credentials',
            'Resource not found',
            'Server error (500)',
            'Bad request (400)',
            'Unauthorized (401)',
        ]);
    }

    /**
     * Indicate that the sync was successful.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'woocommerce_id' => $this->faker->numberBetween(1, 10000),
            'response_data' => $this->generateSuccessResponse(),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the sync failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'woocommerce_id' => null,
            'response_data' => null,
            'error_message' => $this->generateErrorMessage(),
        ]);
    }

    /**
     * Create a log for creating a record.
     */
    public function create(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'create',
        ]);
    }

    /**
     * Create a log for updating a record.
     */
    public function update(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'update',
        ]);
    }

    /**
     * Create a log for deleting a record.
     */
    public function delete(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'delete',
        ]);
    }

    /**
     * Create a log for an Order.
     */
    public function forOrder($order = null): static
    {
        return $this->state(fn (array $attributes) => [
            'syncable_type' => \App\Models\Order::class,
            'syncable_id' => $order?->id ?? \App\Models\Order::factory(),
        ]);
    }

    /**
     * Create a log for a Customer.
     */
    public function forCustomer($customer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'syncable_type' => \App\Models\Customer::class,
            'syncable_id' => $customer?->id ?? \App\Models\Customer::factory(),
        ]);
    }

    /**
     * Create a log with fast sync duration.
     */
    public function fast(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration' => $this->faker->randomFloat(2, 0.1, 1.0),
        ]);
    }

    /**
     * Create a log with slow sync duration.
     */
    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration' => $this->faker->randomFloat(2, 3.0, 10.0),
        ]);
    }
}