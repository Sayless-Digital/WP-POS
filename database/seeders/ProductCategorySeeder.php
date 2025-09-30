<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding product categories...');

        // Create main categories
        $electronics = ProductCategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and accessories',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $clothing = ProductCategory::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
            'description' => 'Apparel and fashion items',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $food = ProductCategory::create([
            'name' => 'Food & Beverages',
            'slug' => 'food-beverages',
            'description' => 'Food items and drinks',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $home = ProductCategory::create([
            'name' => 'Home & Garden',
            'slug' => 'home-garden',
            'description' => 'Home improvement and garden supplies',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $sports = ProductCategory::create([
            'name' => 'Sports & Outdoors',
            'slug' => 'sports-outdoors',
            'description' => 'Sports equipment and outdoor gear',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        // Create subcategories for Electronics
        ProductCategory::create([
            'name' => 'Computers',
            'slug' => 'computers',
            'description' => 'Laptops, desktops, and accessories',
            'parent_id' => $electronics->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Mobile Phones',
            'slug' => 'mobile-phones',
            'description' => 'Smartphones and accessories',
            'parent_id' => $electronics->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Audio & Video',
            'slug' => 'audio-video',
            'description' => 'Headphones, speakers, and cameras',
            'parent_id' => $electronics->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create subcategories for Clothing
        ProductCategory::create([
            'name' => 'Men\'s Clothing',
            'slug' => 'mens-clothing',
            'description' => 'Clothing for men',
            'parent_id' => $clothing->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Women\'s Clothing',
            'slug' => 'womens-clothing',
            'description' => 'Clothing for women',
            'parent_id' => $clothing->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Kids\' Clothing',
            'slug' => 'kids-clothing',
            'description' => 'Clothing for children',
            'parent_id' => $clothing->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create subcategories for Food & Beverages
        ProductCategory::create([
            'name' => 'Snacks',
            'slug' => 'snacks',
            'description' => 'Chips, cookies, and other snacks',
            'parent_id' => $food->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Beverages',
            'slug' => 'beverages',
            'description' => 'Soft drinks, juices, and water',
            'parent_id' => $food->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Fresh Produce',
            'slug' => 'fresh-produce',
            'description' => 'Fruits and vegetables',
            'parent_id' => $food->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create subcategories for Home & Garden
        ProductCategory::create([
            'name' => 'Furniture',
            'slug' => 'furniture',
            'description' => 'Home and office furniture',
            'parent_id' => $home->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Kitchen & Dining',
            'slug' => 'kitchen-dining',
            'description' => 'Cookware and dining essentials',
            'parent_id' => $home->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Garden Tools',
            'slug' => 'garden-tools',
            'description' => 'Tools and equipment for gardening',
            'parent_id' => $home->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create subcategories for Sports & Outdoors
        ProductCategory::create([
            'name' => 'Fitness Equipment',
            'slug' => 'fitness-equipment',
            'description' => 'Exercise and fitness gear',
            'parent_id' => $sports->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Camping & Hiking',
            'slug' => 'camping-hiking',
            'description' => 'Outdoor adventure gear',
            'parent_id' => $sports->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        ProductCategory::create([
            'name' => 'Team Sports',
            'slug' => 'team-sports',
            'description' => 'Equipment for team sports',
            'parent_id' => $sports->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $this->command->info('Product categories seeded successfully!');
        $this->command->info('Created 5 main categories and 15 subcategories');
    }
}