<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user with admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin User',
            'email' => 'admin@pos.test',
        ])->afterCreating(function ($user) {
            $user->assignRole('admin');
        });
    }

    /**
     * Create a cashier user with cashier role.
     */
    public function cashier(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ])->afterCreating(function ($user) {
            $user->assignRole('cashier');
        });
    }

    /**
     * Create a manager user with manager role.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ])->afterCreating(function ($user) {
            $user->assignRole('manager');
        });
    }

    /**
     * Create an inventory manager user.
     */
    public function inventoryManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ])->afterCreating(function ($user) {
            $user->assignRole('inventory_manager');
        });
    }
}
