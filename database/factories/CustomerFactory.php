<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_group_id' => CustomerGroup::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'address' => $this->faker->optional()->streetAddress(),
            'city' => $this->faker->optional()->city(),
            'state' => $this->faker->optional()->state(),
            'postal_code' => $this->faker->optional()->postcode(),
            'country' => $this->faker->optional()->country(),
            'loyalty_points' => $this->faker->numberBetween(0, 1000),
            'total_spent' => $this->faker->randomFloat(2, 0, 10000),
            'total_orders' => $this->faker->numberBetween(0, 100),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'woocommerce_id' => null,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer is synced with WooCommerce.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'woocommerce_id' => $this->faker->unique()->numberBetween(1, 10000),
            'last_synced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a VIP customer with high loyalty points and spending.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_group_id' => CustomerGroup::factory()->vip(),
            'loyalty_points' => $this->faker->numberBetween(5000, 10000),
            'total_spent' => $this->faker->randomFloat(2, 10000, 50000),
            'total_orders' => $this->faker->numberBetween(50, 200),
        ]);
    }

    /**
     * Create a new customer with minimal history.
     */
    public function newCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => 0,
            'total_spent' => 0,
            'total_orders' => 0,
        ]);
    }

    /**
     * Create a customer with high loyalty points.
     */
    public function withLoyaltyPoints(int $points = null): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => $points ?? $this->faker->numberBetween(1000, 5000),
        ]);
    }

    /**
     * Create a customer with complete address information.
     */
    public function withAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
        ]);
    }

    /**
     * Create a wholesale customer.
     */
    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_group_id' => CustomerGroup::factory()->wholesale(),
            'total_spent' => $this->faker->randomFloat(2, 20000, 100000),
            'total_orders' => $this->faker->numberBetween(100, 500),
        ]);
    }
}