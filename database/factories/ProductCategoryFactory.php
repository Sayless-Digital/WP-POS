<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'slug' => fn (array $attributes) => \Illuminate\Support\Str::slug($attributes['name']),
            'description' => $this->faker->optional()->sentence(),
            'parent_id' => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
            'woocommerce_id' => null,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the category is a child of another category.
     */
    public function child(?ProductCategory $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? ProductCategory::factory(),
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category is synced with WooCommerce.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'woocommerce_id' => $this->faker->unique()->numberBetween(1, 10000),
            'last_synced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}