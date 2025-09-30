<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(0, 500);
        $reserved = $this->faker->numberBetween(0, min(50, $quantity));
        
        return [
            'inventoriable_type' => Product::class,
            'inventoriable_id' => Product::factory(),
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
            'reorder_point' => $this->faker->numberBetween(10, 50),
            'reorder_quantity' => $this->faker->numberBetween(50, 200),
        ];
    }

    /**
     * Indicate that this inventory is for a product variant.
     */
    public function forVariant($variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'inventoriable_type' => \App\Models\ProductVariant::class,
            'inventoriable_id' => $variant?->id ?? \App\Models\ProductVariant::factory(),
        ]);
    }

    /**
     * Indicate that the inventory is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the inventory is low stock (below reorder point).
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $reorderPoint = $attributes['reorder_point'];
            return [
                'quantity' => $this->faker->numberBetween(1, $reorderPoint - 1),
                'reserved_quantity' => 0,
            ];
        });
    }

    /**
     * Indicate that the inventory has reserved quantity.
     */
    public function withReservations(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            return [
                'reserved_quantity' => $this->faker->numberBetween(1, min(50, $quantity)),
            ];
        });
    }

    /**
     * Indicate that the inventory is well stocked.
     */
    public function wellStocked(): static
    {
        return $this->state(function (array $attributes) {
            $reorderPoint = $attributes['reorder_point'];
            return [
                'quantity' => $this->faker->numberBetween($reorderPoint * 2, $reorderPoint * 10),
                'reserved_quantity' => 0,
            ];
        });
    }
}