<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding customer groups...');

        // Regular customers - no discount
        CustomerGroup::create([
            'name' => 'Regular',
            'description' => 'Regular retail customers with no special discounts',
            'discount_percentage' => 0,
            'is_active' => true,
        ]);

        // Bronze tier - 5% discount
        CustomerGroup::create([
            'name' => 'Bronze',
            'description' => 'Bronze tier customers - 5% discount on all purchases',
            'discount_percentage' => 5.00,
            'is_active' => true,
        ]);

        // Silver tier - 10% discount
        CustomerGroup::create([
            'name' => 'Silver',
            'description' => 'Silver tier customers - 10% discount on all purchases',
            'discount_percentage' => 10.00,
            'is_active' => true,
        ]);

        // Gold tier - 15% discount
        CustomerGroup::create([
            'name' => 'Gold',
            'description' => 'Gold tier customers - 15% discount on all purchases',
            'discount_percentage' => 15.00,
            'is_active' => true,
        ]);

        // VIP tier - 20% discount
        CustomerGroup::create([
            'name' => 'VIP',
            'description' => 'VIP customers - 20% discount on all purchases with premium benefits',
            'discount_percentage' => 20.00,
            'is_active' => true,
        ]);

        // Wholesale - 25% discount
        CustomerGroup::create([
            'name' => 'Wholesale',
            'description' => 'Wholesale customers buying in bulk - 25% discount',
            'discount_percentage' => 25.00,
            'is_active' => true,
        ]);

        // Employee - 30% discount
        CustomerGroup::create([
            'name' => 'Employee',
            'description' => 'Company employees - 30% discount on all purchases',
            'discount_percentage' => 30.00,
            'is_active' => true,
        ]);

        $this->command->info('Customer groups seeded successfully!');
        $this->command->info('Created 7 customer groups with varying discount tiers');
    }
}