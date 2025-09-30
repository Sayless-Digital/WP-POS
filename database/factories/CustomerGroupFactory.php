<?php

namespace Database\Factories;

use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'discount_percentage' => $this->faker->randomFloat(2, 0, 25),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a VIP customer group with high discount.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'VIP',
            'description' => 'VIP customers with premium benefits',
            'discount_percentage' => $this->faker->randomFloat(2, 15, 25),
        ]);
    }

    /**
     * Create a wholesale customer group.
     */
    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Wholesale',
            'description' => 'Wholesale customers buying in bulk',
            'discount_percentage' => $this->faker->randomFloat(2, 20, 30),
        ]);
    }

    /**
     * Create a regular customer group with no discount.
     */
    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Regular',
            'description' => 'Regular retail customers',
            'discount_percentage' => 0,
        ]);
    }

    /**
     * Create a premium customer group with moderate discount.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium',
            'description' => 'Premium customers with loyalty benefits',
            'discount_percentage' => $this->faker->randomFloat(2, 10, 15),
        ]);
    }
}