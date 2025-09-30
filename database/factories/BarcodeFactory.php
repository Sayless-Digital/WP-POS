<?php

namespace Database\Factories;

use App\Models\Barcode;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Barcode>
 */
class BarcodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['EAN13', 'EAN8', 'UPC', 'CODE128']);
        
        return [
            'barcodeable_type' => Product::class,
            'barcodeable_id' => Product::factory(),
            'barcode' => $this->generateBarcode($type),
            'type' => $type,
            'is_primary' => false,
        ];
    }

    /**
     * Generate a valid barcode based on type.
     */
    protected function generateBarcode(string $type): string
    {
        return match($type) {
            'EAN13' => $this->faker->ean13(),
            'EAN8' => $this->faker->ean8(),
            'UPC' => substr($this->faker->ean13(), 0, 12), // UPC is 12 digits
            'CODE128' => strtoupper($this->faker->bothify('??########')),
            default => $this->faker->ean13(),
        };
    }

    /**
     * Indicate that this is the primary barcode.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that this barcode is for a product variant.
     */
    public function forVariant($variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'barcodeable_type' => \App\Models\ProductVariant::class,
            'barcodeable_id' => $variant?->id ?? \App\Models\ProductVariant::factory(),
        ]);
    }

    /**
     * Create an EAN13 barcode.
     */
    public function ean13(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'EAN13',
            'barcode' => $this->faker->ean13(),
        ]);
    }

    /**
     * Create an EAN8 barcode.
     */
    public function ean8(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'EAN8',
            'barcode' => $this->faker->ean8(),
        ]);
    }

    /**
     * Create a UPC barcode.
     */
    public function upc(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'UPC',
            'barcode' => substr($this->faker->ean13(), 0, 12),
        ]);
    }

    /**
     * Create a CODE128 barcode.
     */
    public function code128(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'CODE128',
            'barcode' => strtoupper($this->faker->bothify('??########')),
        ]);
    }
}