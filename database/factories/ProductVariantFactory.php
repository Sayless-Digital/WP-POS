<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
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
            'product_id' => Product::factory()->withVariants(),
            'name' => $this->faker->words(2, true),
            'sku' => strtoupper($this->faker->unique()->bothify('VAR-####??')),
            'cost_price' => $cost,
            'selling_price' => $price,
            'compare_at_price' => $this->faker->optional(0.3)->randomFloat(2, $price * 1.1, $price * 1.5),
            'option1_name' => 'Size',
            'option1_value' => $this->faker->randomElement(['Small', 'Medium', 'Large', 'XL']),
            'option2_name' => null,
            'option2_value' => null,
            'option3_name' => null,
            'option3_value' => null,
            'is_active' => true,
            'image_url' => $this->faker->optional(0.5)->imageUrl(640, 480, 'products', true),
            'woocommerce_id' => null,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the variant has two options (e.g., Size and Color).
     */
    public function withTwoOptions(): static
    {
        return $this->state(fn (array $attributes) => [
            'option2_name' => 'Color',
            'option2_value' => $this->faker->randomElement(['Red', 'Blue', 'Green', 'Black', 'White']),
        ]);
    }

    /**
     * Indicate that the variant has three options.
     */
    public function withThreeOptions(): static
    {
        return $this->state(fn (array $attributes) => [
            'option2_name' => 'Color',
            'option2_value' => $this->faker->randomElement(['Red', 'Blue', 'Green', 'Black', 'White']),
            'option3_name' => 'Material',
            'option3_value' => $this->faker->randomElement(['Cotton', 'Polyester', 'Silk', 'Wool']),
        ]);
    }

    /**
     * Indicate that the variant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the variant is synced with WooCommerce.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'woocommerce_id' => $this->faker->unique()->numberBetween(1, 10000),
            'last_synced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the variant has a compare at price (on sale).
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