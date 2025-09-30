<?php

namespace Database\Seeders;

use App\Models\Barcode;
use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\HeldOrder;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        // 1. Seed roles and permissions first
        $this->command->info('📋 Seeding roles and permissions...');
        $this->call(RoleAndPermissionSeeder::class);
        $this->command->newLine();

        // 2. Create users with roles
        $this->command->info('👥 Creating users...');
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $cashier1 = User::factory()->cashier()->create(['name' => 'John Cashier']);
        $cashier2 = User::factory()->cashier()->create(['name' => 'Jane Cashier']);
        $inventoryManager = User::factory()->inventoryManager()->create();
        $this->command->info("Created 5 users (1 admin, 1 manager, 2 cashiers, 1 inventory manager)");
        $this->command->newLine();

        // 3. Seed product categories
        $this->command->info('📁 Seeding product categories...');
        $this->call(ProductCategorySeeder::class);
        $this->command->newLine();

        // 4. Seed customer groups
        $this->command->info('👨‍👩‍👧‍👦 Seeding customer groups...');
        $this->call(CustomerGroupSeeder::class);
        $this->command->newLine();

        // 5. Create products with variants, barcodes, and inventory
        $this->command->info('📦 Creating products...');
        $categories = ProductCategory::whereNull('parent_id')->get();
        
        // Create 50 simple products (no variants)
        $simpleProducts = Product::factory()
            ->count(50)
            ->create()
            ->each(function ($product) {
                // Add barcode
                Barcode::factory()->primary()->create([
                    'barcodeable_type' => Product::class,
                    'barcodeable_id' => $product->id,
                ]);
                
                // Add inventory
                Inventory::factory()->wellStocked()->create([
                    'inventoriable_type' => Product::class,
                    'inventoriable_id' => $product->id,
                ]);
            });
        
        $this->command->info("Created 50 simple products with barcodes and inventory");

        // Create 20 products with variants
        $variantProducts = Product::factory()
            ->withVariants()
            ->count(20)
            ->create()
            ->each(function ($product) {
                // Create 3-5 variants per product
                $variantCount = rand(3, 5);
                for ($i = 0; $i < $variantCount; $i++) {
                    $variant = ProductVariant::factory()->create([
                        'product_id' => $product->id,
                    ]);
                    
                    // Add barcode to variant
                    Barcode::factory()->primary()->forVariant($variant)->create();
                    
                    // Add inventory to variant
                    Inventory::factory()->wellStocked()->forVariant($variant)->create();
                }
            });
        
        $this->command->info("Created 20 products with variants (60-100 variants total)");
        $this->command->newLine();

        // 6. Create customers
        $this->command->info('👤 Creating customers...');
        $customers = Customer::factory()->count(100)->create();
        $this->command->info("Created 100 customers");
        $this->command->newLine();

        // 7. Create orders with items and payments
        $this->command->info('🛒 Creating orders...');
        
        // Create 50 completed orders
        Order::factory()
            ->count(50)
            ->completed()
            ->create()
            ->each(function ($order) {
                // Add 2-5 items per order
                $itemCount = rand(2, 5);
                $products = Product::inRandomOrder()->limit($itemCount)->get();
                
                foreach ($products as $product) {
                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                    ]);
                }
                
                // Add payment
                Payment::factory()->completed()->create([
                    'order_id' => $order->id,
                    'amount' => $order->total,
                ]);
            });
        
        $this->command->info("Created 50 completed orders with items and payments");

        // Create 10 pending orders
        Order::factory()
            ->count(10)
            ->pending()
            ->create()
            ->each(function ($order) {
                $itemCount = rand(1, 3);
                $products = Product::inRandomOrder()->limit($itemCount)->get();
                
                foreach ($products as $product) {
                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                    ]);
                }
            });
        
        $this->command->info("Created 10 pending orders");
        $this->command->newLine();

        // 8. Create some refunds
        $this->command->info('💰 Creating refunds...');
        $completedOrders = Order::where('status', 'completed')->limit(5)->get();
        foreach ($completedOrders as $order) {
            $payment = $order->payments()->first();
            if ($payment) {
                Refund::factory()->completed()->create([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'amount' => $order->total * 0.5, // Partial refund
                ]);
            }
        }
        $this->command->info("Created 5 refunds");
        $this->command->newLine();

        // 9. Create held orders
        $this->command->info('⏸️  Creating held orders...');
        HeldOrder::factory()->count(5)->create();
        $this->command->info("Created 5 held orders");
        $this->command->newLine();

        // 10. Create cash drawer sessions
        $this->command->info('💵 Creating cash drawer sessions...');
        
        // Create 10 closed sessions
        CashDrawerSession::factory()
            ->count(10)
            ->closed()
            ->create()
            ->each(function ($session) {
                // Add some cash movements
                CashMovement::factory()->startingFloat()->create([
                    'cash_drawer_session_id' => $session->id,
                ]);
                CashMovement::factory()->bankDeposit()->create([
                    'cash_drawer_session_id' => $session->id,
                ]);
            });
        
        // Create 2 open sessions
        CashDrawerSession::factory()
            ->count(2)
            ->open()
            ->create()
            ->each(function ($session) {
                CashMovement::factory()->startingFloat()->create([
                    'cash_drawer_session_id' => $session->id,
                ]);
            });
        
        $this->command->info("Created 12 cash drawer sessions (10 closed, 2 open)");
        $this->command->newLine();

        // 11. Create stock movements
        $this->command->info('📊 Creating stock movements...');
        $products = Product::limit(20)->get();
        foreach ($products as $product) {
            // Purchase
            StockMovement::factory()->purchase()->create([
                'inventoriable_type' => Product::class,
                'inventoriable_id' => $product->id,
            ]);
            
            // Sale
            StockMovement::factory()->sale()->create([
                'inventoriable_type' => Product::class,
                'inventoriable_id' => $product->id,
            ]);
        }
        $this->command->info("Created 40 stock movements");
        $this->command->newLine();

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        
        // Summary
        $this->command->info('📊 Summary:');
        $this->command->info('  - Users: ' . User::count());
        $this->command->info('  - Product Categories: ' . ProductCategory::count());
        $this->command->info('  - Products: ' . Product::count());
        $this->command->info('  - Product Variants: ' . ProductVariant::count());
        $this->command->info('  - Customers: ' . Customer::count());
        $this->command->info('  - Orders: ' . Order::count());
        $this->command->info('  - Order Items: ' . OrderItem::count());
        $this->command->info('  - Payments: ' . Payment::count());
        $this->command->info('  - Refunds: ' . Refund::count());
        $this->command->info('  - Held Orders: ' . HeldOrder::count());
        $this->command->info('  - Cash Drawer Sessions: ' . CashDrawerSession::count());
        $this->command->info('  - Stock Movements: ' . StockMovement::count());
    }
}
