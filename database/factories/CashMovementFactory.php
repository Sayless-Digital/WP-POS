<?php

namespace Database\Factories;

use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CashMovement>
 */
class CashMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['in', 'out']);
        
        return [
            'cash_drawer_session_id' => CashDrawerSession::factory(),
            'user_id' => User::factory(),
            'type' => $type,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'reason' => $this->generateReason($type),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Generate a reason based on movement type.
     */
    protected function generateReason(string $type): string
    {
        if ($type === 'in') {
            return $this->faker->randomElement([
                'Starting float',
                'Bank deposit return',
                'Cash found',
                'Correction',
                'Other income',
            ]);
        }
        
        return $this->faker->randomElement([
            'Bank deposit',
            'Petty cash',
            'Supplies purchase',
            'Expense payment',
            'Cash withdrawal',
        ]);
    }

    /**
     * Create a cash in movement.
     */
    public function cashIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
            'reason' => $this->generateReason('in'),
        ]);
    }

    /**
     * Create a cash out movement.
     */
    public function cashOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'reason' => $this->generateReason('out'),
        ]);
    }

    /**
     * Create a starting float movement.
     */
    public function startingFloat(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
            'amount' => $this->faker->randomFloat(2, 100, 500),
            'reason' => 'Starting float',
            'notes' => 'Opening cash drawer with starting float',
        ]);
    }

    /**
     * Create a bank deposit movement.
     */
    public function bankDeposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'amount' => $this->faker->randomFloat(2, 500, 5000),
            'reason' => 'Bank deposit',
            'notes' => 'Cash deposited to bank',
        ]);
    }

    /**
     * Create a petty cash movement.
     */
    public function pettyCash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'reason' => 'Petty cash',
            'notes' => 'Petty cash expense',
        ]);
    }

    /**
     * Create a supplies purchase movement.
     */
    public function suppliesPurchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'amount' => $this->faker->randomFloat(2, 20, 200),
            'reason' => 'Supplies purchase',
            'notes' => 'Office supplies purchased',
        ]);
    }

    /**
     * Create a large amount movement.
     */
    public function largeAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->randomFloat(2, 1000, 10000),
        ]);
    }
}