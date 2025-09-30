<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['purchase', 'sale', 'adjustment', 'return', 'damage', 'transfer']);
        $quantity = $this->faker->numberBetween(1, 100);
        
        return [
            'inventoriable_type' => Product::class,
            'inventoriable_id' => Product::factory(),
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $this->faker->numberBetween(0, 500),
            'quantity_after' => fn (array $attributes) => $attributes['quantity_before'] + ($this->isIncrease($type) ? $quantity : -$quantity),
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $this->faker->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Determine if the movement type increases inventory.
     */
    protected function isIncrease(string $type): bool
    {
        return in_array($type, ['purchase', 'return', 'adjustment']);
    }

    /**
     * Indicate that this movement is for a product variant.
     */
    public function forVariant($variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'inventoriable_type' => \App\Models\ProductVariant::class,
            'inventoriable_id' => $variant?->id ?? \App\Models\ProductVariant::factory(),
        ]);
    }

    /**
     * Create a purchase movement.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'purchase',
            'notes' => 'Stock purchased from supplier',
        ]);
    }

    /**
     * Create a sale movement.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sale',
            'reference_type' => \App\Models\Order::class,
            'reference_id' => \App\Models\Order::factory(),
            'notes' => 'Sold to customer',
        ]);
    }

    /**
     * Create an adjustment movement.
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'adjustment',
            'notes' => $this->faker->randomElement([
                'Stock count adjustment',
                'Inventory correction',
                'Manual adjustment',
            ]),
        ]);
    }

    /**
     * Create a return movement.
     */
    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'return',
            'reference_type' => \App\Models\Refund::class,
            'reference_id' => \App\Models\Refund::factory(),
            'notes' => 'Customer return',
        ]);
    }

    /**
     * Create a damage movement.
     */
    public function damage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'damage',
            'notes' => $this->faker->randomElement([
                'Damaged during handling',
                'Expired product',
                'Quality issue',
            ]),
        ]);
    }

    /**
     * Create a transfer movement.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'notes' => 'Stock transfer between locations',
        ]);
    }
}