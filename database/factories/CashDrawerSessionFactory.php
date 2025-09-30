<?php

namespace Database\Factories;

use App\Models\CashDrawerSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CashDrawerSession>
 */
class CashDrawerSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $openingBalance = $this->faker->randomFloat(2, 100, 1000);
        $cashSales = $this->faker->randomFloat(2, 500, 5000);
        $cashIn = $this->faker->randomFloat(2, 0, 500);
        $cashOut = $this->faker->randomFloat(2, 0, 300);
        $expectedBalance = $openingBalance + $cashSales + $cashIn - $cashOut;
        $actualBalance = $expectedBalance + $this->faker->randomFloat(2, -50, 50); // Small variance
        
        return [
            'user_id' => User::factory(),
            'opened_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'closed_at' => null,
            'opening_balance' => $openingBalance,
            'closing_balance' => null,
            'expected_balance' => null,
            'cash_sales' => 0,
            'cash_refunds' => 0,
            'cash_in' => 0,
            'cash_out' => 0,
            'discrepancy' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the session is closed.
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $openingBalance = $attributes['opening_balance'];
            $cashSales = $this->faker->randomFloat(2, 500, 5000);
            $cashRefunds = $this->faker->randomFloat(2, 0, 200);
            $cashIn = $this->faker->randomFloat(2, 0, 500);
            $cashOut = $this->faker->randomFloat(2, 0, 300);
            $expectedBalance = $openingBalance + $cashSales - $cashRefunds + $cashIn - $cashOut;
            $actualBalance = $expectedBalance + $this->faker->randomFloat(2, -50, 50);
            
            return [
                'closed_at' => $this->faker->dateTimeBetween($attributes['opened_at'], 'now'),
                'closing_balance' => $actualBalance,
                'expected_balance' => $expectedBalance,
                'cash_sales' => $cashSales,
                'cash_refunds' => $cashRefunds,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'discrepancy' => $actualBalance - $expectedBalance,
            ];
        });
    }

    /**
     * Indicate that the session is currently open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'opened_at' => now()->subHours($this->faker->numberBetween(1, 8)),
            'closed_at' => null,
            'closing_balance' => null,
            'expected_balance' => null,
            'discrepancy' => null,
        ]);
    }

    /**
     * Create a session with no discrepancy.
     */
    public function balanced(): static
    {
        return $this->state(function (array $attributes) {
            $openingBalance = $attributes['opening_balance'];
            $cashSales = $this->faker->randomFloat(2, 500, 5000);
            $cashRefunds = $this->faker->randomFloat(2, 0, 200);
            $cashIn = $this->faker->randomFloat(2, 0, 500);
            $cashOut = $this->faker->randomFloat(2, 0, 300);
            $expectedBalance = $openingBalance + $cashSales - $cashRefunds + $cashIn - $cashOut;
            
            return [
                'closed_at' => $this->faker->dateTimeBetween($attributes['opened_at'], 'now'),
                'closing_balance' => $expectedBalance,
                'expected_balance' => $expectedBalance,
                'cash_sales' => $cashSales,
                'cash_refunds' => $cashRefunds,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'discrepancy' => 0,
            ];
        });
    }

    /**
     * Create a session with a significant discrepancy.
     */
    public function withDiscrepancy(): static
    {
        return $this->state(function (array $attributes) {
            $openingBalance = $attributes['opening_balance'];
            $cashSales = $this->faker->randomFloat(2, 500, 5000);
            $cashRefunds = $this->faker->randomFloat(2, 0, 200);
            $cashIn = $this->faker->randomFloat(2, 0, 500);
            $cashOut = $this->faker->randomFloat(2, 0, 300);
            $expectedBalance = $openingBalance + $cashSales - $cashRefunds + $cashIn - $cashOut;
            $discrepancy = $this->faker->randomFloat(2, -200, 200);
            $actualBalance = $expectedBalance + $discrepancy;
            
            return [
                'closed_at' => $this->faker->dateTimeBetween($attributes['opened_at'], 'now'),
                'closing_balance' => $actualBalance,
                'expected_balance' => $expectedBalance,
                'cash_sales' => $cashSales,
                'cash_refunds' => $cashRefunds,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'discrepancy' => $discrepancy,
                'notes' => 'Discrepancy of ' . number_format(abs($discrepancy), 2) . ' detected',
            ];
        });
    }

    /**
     * Create a session with high sales volume.
     */
    public function highVolume(): static
    {
        return $this->state(function (array $attributes) {
            $openingBalance = $attributes['opening_balance'];
            $cashSales = $this->faker->randomFloat(2, 10000, 20000);
            $cashRefunds = $this->faker->randomFloat(2, 500, 1000);
            $cashIn = $this->faker->randomFloat(2, 0, 1000);
            $cashOut = $this->faker->randomFloat(2, 0, 500);
            $expectedBalance = $openingBalance + $cashSales - $cashRefunds + $cashIn - $cashOut;
            $actualBalance = $expectedBalance + $this->faker->randomFloat(2, -100, 100);
            
            return [
                'closed_at' => $this->faker->dateTimeBetween($attributes['opened_at'], 'now'),
                'closing_balance' => $actualBalance,
                'expected_balance' => $expectedBalance,
                'cash_sales' => $cashSales,
                'cash_refunds' => $cashRefunds,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'discrepancy' => $actualBalance - $expectedBalance,
            ];
        });
    }
}