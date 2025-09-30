<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = $this->faker->randomFloat(2, 5, 500);
        $price = $cost * $this->faker->randomFloat(2, 1.2, 2.5); // 20-150% markup

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'slug' => fn (array $attributes) => \Illuminate\Support\Str::slug($attributes['name']),
            'description' => $this->faker->optional()->paragraph(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####??')),
            'cost_price' => $cost,
            'selling_price' => $price,
            'compare_at_price' => $this->faker->optional(0.3)->randomFloat(2, $price * 1.1, $price * 1.5),
            'tax_rate' => $this->faker->randomElement([0, 5, 10, 12.5, 15]),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(20),
            'track_inventory' => true,
            'allow_backorder' => false,
            'has_variants' => false,
            'image_url' => $this->faker->optional(0.7)->imageUrl(640, 480, 'products', true),
            'woocommerce_id' => null,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the product has variants.
     */
    public function withVariants(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_variants' => true,
            'sku' => null, // Parent products with variants don't have their own SKU
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product doesn't track inventory.
     */
    public function noInventoryTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_inventory' => false,
        ]);
    }

    /**
     * Indicate that the product allows backorders.
     */
    public function allowBackorder(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_backorder' => true,
        ]);
    }

    /**
     * Indicate that the product is synced with WooCommerce.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'woocommerce_id' => $this->faker->unique()->numberBetween(1, 10000),
            'last_synced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the product has a compare at price (on sale).
     */
    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $sellingPrice = $attributes['selling_price'];
            return [
                'compare_at_price' => $this->faker->randomFloat(2, $sellingPrice * 1.1, $sellingPrice * 1.5),
            ];
        });
    }
}