# Quick Start Guide - Laravel POS System

## Complete Step-by-Step Implementation Guide for Beginners

This guide walks you through building the POS system from scratch, with detailed commands and explanations for each step.

---

## Prerequisites

### Required Software
- **PHP 8.1+** - [Download](https://www.php.net/downloads)
- **Composer** - [Download](https://getcomposer.org/download/)
- **MySQL 5.7+** - [Download](https://dev.mysql.com/downloads/)
- **Git** - [Download](https://git-scm.com/downloads)
- **Code Editor** - VS Code recommended

### Verify Installation
```bash
php -v        # Should show PHP 8.1 or higher
composer -V   # Should show Composer version
mysql --version  # Should show MySQL version
git --version    # Should show Git version
```

---

## Part 1: Project Setup (Day 1)

### Step 1: Create Laravel Project

```bash
# Navigate to your projects directory
cd ~/Documents/Projects

# Create new Laravel project
composer create-project laravel/laravel WP-POS

# Navigate into project
cd WP-POS

# Test installation
php artisan serve
```

Visit `http://localhost:8000` - you should see Laravel welcome page.

### Step 2: Install Dependencies

```bash
# Install Livewire
composer require livewire/livewire

# Install WooCommerce SDK
composer require automattic/woocommerce

# Install PDF generator
composer require barryvdh/laravel-dompdf

# Install permissions package
composer require spatie/laravel-permission
```

### Step 3: Configure Database

**Create Database:**
```sql
-- Using MySQL command line or phpMyAdmin
CREATE DATABASE pos_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Configure .env:**
```env
APP_NAME="POS System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Test Database Connection:**
```bash
php artisan migrate
```

If successful, you'll see migration messages.

### Step 4: Setup Authentication

```bash
# Install Laravel Breeze (simple authentication)
composer require laravel/breeze --dev

# Install Breeze with Livewire
php artisan breeze:install livewire

# Run migrations
php artisan migrate
```

**Test Authentication:**
```bash
php artisan serve
```

Visit `http://localhost:8000/register` and create an account.

---

## Part 2: Database Schema (Day 2-3)

### Step 1: Create Migrations

```bash
# Core tables
php artisan make:migration create_roles_table
php artisan make:migration create_permissions_table
php artisan make:migration create_role_permissions_table
php artisan make:migration create_products_table
php artisan make:migration create_product_variants_table
php artisan make:migration create_product_categories_table
php artisan make:migration create_barcodes_table
php artisan make:migration create_inventory_table
php artisan make:migration create_stock_movements_table
php artisan make:migration create_customers_table
php artisan make:migration create_customer_groups_table
php artisan make:migration create_orders_table
php artisan make:migration create_order_items_table
php artisan make:migration create_payments_table
php artisan make:migration create_refunds_table
php artisan make:migration create_held_orders_table
php artisan make:migration create_sync_queue_table
php artisan make:migration create_sync_logs_table
php artisan make:migration create_cash_drawer_sessions_table
php artisan make:migration create_cash_movements_table
```

### Step 2: Define Migration Schema

**Example: Products Table**

Edit `database/migrations/xxxx_create_products_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('woocommerce_id')->nullable();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['simple', 'variable'])->default('simple');
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('track_inventory')->default(true);
            $table->string('image_url', 500)->nullable();
            $table->timestamps();
            $table->timestamp('synced_at')->nullable();
            
            $table->index('sku');
            $table->index('woocommerce_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

**Repeat for all tables** using the schema from [`POS_Development_Plan.md`](POS_Development_Plan.md:660).

### Step 3: Run Migrations

```bash
php artisan migrate
```

If you get errors, fix them and run:
```bash
php artisan migrate:fresh
```

---

## Part 3: Models & Relationships (Day 4)

### Step 1: Create Models

```bash
php artisan make:model Role
php artisan make:model Permission
php artisan make:model Product
php artisan make:model ProductVariant
php artisan make:model ProductCategory
php artisan make:model Barcode
php artisan make:model Inventory
php artisan make:model StockMovement
php artisan make:model Customer
php artisan make:model CustomerGroup
php artisan make:model Order
php artisan make:model OrderItem
php artisan make:model Payment
php artisan make:model Refund
php artisan make:model HeldOrder
php artisan make:model SyncQueue
php artisan make:model SyncLog
php artisan make:model CashDrawerSession
php artisan make:model CashMovement
```

### Step 2: Define Relationships

**Example: Product Model**

Edit `app/Models/Product.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'woocommerce_id',
        'sku',
        'name',
        'description',
        'type',
        'price',
        'cost_price',
        'category_id',
        'tax_rate',
        'is_active',
        'track_inventory',
        'image_url',
        'synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
        'synced_at' => 'datetime',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventory(): MorphOne
    {
        return $this->morphOne(Inventory::class, 'inventoriable');
    }

    public function barcodes(): MorphMany
    {
        return $this->morphMany(Barcode::class, 'barcodeable');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'inventoriable');
    }

    // Accessors
    public function getStockQuantityAttribute()
    {
        return $this->inventory?->quantity ?? 0;
    }

    public function getIsLowStockAttribute()
    {
        if (!$this->track_inventory) {
            return false;
        }
        
        return $this->stock_quantity <= ($this->inventory?->low_stock_threshold ?? 10);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('inventory', function($q) {
            $q->where('quantity', '>', 0);
        });
    }
}
```

**Repeat for all models** with appropriate relationships.

### Step 3: Create Seeders

```bash
php artisan make:seeder RolePermissionSeeder
php artisan make:seeder UserSeeder
php artisan make:seeder ProductCategorySeeder
```

**Example: RolePermissionSeeder**

Edit `database/seeders/RolePermissionSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'pos.access',
            'pos.sell',
            'pos.refund',
            'pos.discount',
            'products.view',
            'products.manage',
            'inventory.view',
            'inventory.manage',
            'inventory.adjust',
            'customers.view',
            'customers.manage',
            'reports.view',
            'users.manage',
            'cash_drawer.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => ucwords(str_replace('.', ' ', $permission)),
            ]);
        }

        // Create roles
        $cashier = Role::create([
            'name' => 'cashier',
            'display_name' => 'Cashier',
        ]);

        $manager = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);

        $storekeeper = Role::create([
            'name' => 'storekeeper',
            'display_name' => 'Storekeeper',
        ]);

        // Assign permissions to roles
        $cashier->permissions()->attach(
            Permission::whereIn('name', [
                'pos.access',
                'pos.sell',
                'products.view',
                'customers.view',
            ])->pluck('id')
        );

        $manager->permissions()->attach(Permission::all()->pluck('id'));

        $storekeeper->permissions()->attach(
            Permission::whereIn('name', [
                'products.view',
                'products.manage',
                'inventory.view',
                'inventory.manage',
                'inventory.adjust',
            ])->pluck('id')
        );
    }
}
```

**Run Seeders:**
```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder
```

---

## Part 4: Livewire Components (Day 5-7)

### Step 1: Create POS Terminal Component

```bash
php artisan make:livewire Pos/PosTerminal
```

This creates:
- `app/Livewire/Pos/PosTerminal.php`
- `resources/views/livewire/pos/pos-terminal.blade.php`

**Edit Component Class:**

```php
<?php

namespace App\Livewire\Pos;

use App\Models\Product;
use Livewire\Component;

class PosTerminal extends Component
{
    public $cart = [];
    public $search = '';
    public $customer = null;

    public function addToCart($productId)
    {
        $product = Product::with('inventory')->find($productId);

        if (!$product) {
            $this->dispatch('error', message: 'Product not found');
            return;
        }

        // Check if already in cart
        $key = array_search($productId, array_column($this->cart, 'product_id'));

        if ($key !== false) {
            $this->cart[$key]['quantity']++;
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'quantity' => 1,
                'tax_rate' => $product->tax_rate,
            ];
        }

        $this->search = '';
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function updateQuantity($index, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);
            return;
        }

        $this->cart[$index]['quantity'] = $quantity;
    }

    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum(function($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    public function getTaxProperty()
    {
        return collect($this->cart)->sum(function($item) {
            $subtotal = $item['price'] * $item['quantity'];
            return $subtotal * ($item['tax_rate'] / 100);
        });
    }

    public function getTotalProperty()
    {
        return $this->subtotal + $this->tax;
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->search, function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('sku', 'like', "%{$this->search}%");
            })
            ->active()
            ->limit(10)
            ->get();

        return view('livewire.pos.pos-terminal', [
            'products' => $products,
        ]);
    }
}
```

**Edit View:**

```blade
<!-- resources/views/livewire/pos/pos-terminal.blade.php -->
<div class="flex h-screen bg-gray-100">
    <!-- Left: Product Search -->
    <div class="w-1/2 p-4 bg-white">
        <input 
            type="text" 
            wire:model.live="search" 
            placeholder="Search products..."
            class="w-full px-4 py-2 border rounded"
        >

        <div class="mt-4 space-y-2">
            @foreach($products as $product)
                <div 
                    wire:click="addToCart({{ $product->id }})"
                    class="p-4 border rounded cursor-pointer hover:bg-gray-50"
                >
                    <div class="font-semibold">{{ $product->name }}</div>
                    <div class="text-sm text-gray-600">{{ $product->sku }}</div>
                    <div class="text-lg font-bold">${{ number_format($product->price, 2) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Right: Cart -->
    <div class="w-1/2 p-4 bg-gray-50">
        <h2 class="text-2xl font-bold mb-4">Cart</h2>

        @if(empty($cart))
            <p class="text-gray-500">Cart is empty</p>
        @else
            <div class="space-y-2">
                @foreach($cart as $index => $item)
                    <div class="p-4 bg-white rounded shadow">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="font-semibold">{{ $item['name'] }}</div>
                                <div class="text-sm text-gray-600">{{ $item['sku'] }}</div>
                            </div>
                            <button 
                                wire:click="removeFromCart({{ $index }})"
                                class="text-red-500 hover:text-red-700"
                            >
                                ×
                            </button>
                        </div>
                        
                        <div class="flex justify-between items-center mt-2">
                            <input 
                                type="number" 
                                wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                value="{{ $item['quantity'] }}"
                                min="1"
                                class="w-20 px-2 py-1 border rounded"
                            >
                            <div class="font-bold">
                                ${{ number_format($item['price'] * $item['quantity'], 2) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Totals -->
            <div class="mt-6 p-4 bg-white rounded shadow">
                <div class="flex justify-between py-2">
                    <span>Subtotal:</span>
                    <span>${{ number_format($this->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span>Tax:</span>
                    <span>${{ number_format($this->tax, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 text-xl font-bold border-t">
                    <span>Total:</span>
                    <span>${{ number_format($this->total, 2) }}</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-4 space-y-2">
                <button class="w-full px-4 py-3 bg-green-500 text-white rounded hover:bg-green-600">
                    Checkout
                </button>
                <button class="w-full px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                    Hold Order
                </button>
            </div>
        @endif
    </div>
</div>
```

### Step 2: Create Route

Edit `routes/web.php`:

```php
use App\Livewire\Pos\PosTerminal;

Route::middleware(['auth'])->group(function () {
    Route::get('/pos', PosTerminal::class)->name('pos.terminal');
});
```

### Step 3: Test POS Terminal

```bash
php artisan serve
```

Visit `http://localhost:8000/pos`

---

## Part 5: Services Layer (Day 8-9)

### Step 1: Create Services

```bash
mkdir app/Services
mkdir app/Services/WooCommerce
```

Create service files:
- `app/Services/ProductService.php`
- `app/Services/InventoryService.php`
- `app/Services/OrderService.php`
- `app/Services/WooCommerce/WooCommerceClient.php`

**Example: InventoryService**

```php
<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function adjustStock($inventoriable, int $quantity, string $type, ?string $notes = null)
    {
        DB::beginTransaction();

        try {
            $inventory = $inventoriable->inventory ?? Inventory::create([
                'inventoriable_type' => get_class($inventoriable),
                'inventoriable_id' => $inventoriable->id,
                'quantity' => 0,
            ]);

            $inventory->increment('quantity', $quantity);

            StockMovement::create([
                'inventoriable_type' => get_class($inventoriable),
                'inventoriable_id' => $inventoriable->id,
                'type' => $type,
                'quantity' => $quantity,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return $inventory;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getStockLevel($inventoriable): int
    {
        return $inventoriable->inventory?->quantity ?? 0;
    }

    public function isInStock($inventoriable, int $quantity = 1): bool
    {
        return $this->getStockLevel($inventoriable) >= $quantity;
    }
}
```

---

## Part 6: Configuration Files (Day 10)

### Step 1: Create Config Files

**Create `config/pos.php`:**

```php
<?php

return [
    'currency' => env('POS_CURRENCY', 'USD'),
    'currency_symbol' => env('POS_CURRENCY_SYMBOL', '$'),
    'tax_rate' => env('POS_TAX_RATE', 0.00),
    'receipt_footer' => env('POS_RECEIPT_FOOTER', 'Thank you for your business!'),
    'low_stock_threshold' => env('POS_LOW_STOCK_THRESHOLD', 10),
    'order_number_prefix' => env('POS_ORDER_PREFIX', 'POS-'),
    'enable_offline_mode' => env('POS_OFFLINE_MODE', true),
    'sync_interval' => env('POS_SYNC_INTERVAL', 300),
];
```

**Create `config/woocommerce.php`:**

```php
<?php

return [
    'store_url' => env('WC_STORE_URL'),
    'consumer_key' => env('WC_CONSUMER_KEY'),
    'consumer_secret' => env('WC_CONSUMER_SECRET'),
    'api_version' => 'wc/v3',
    'verify_ssl' => true,
    'timeout' => 30,
];
```

### Step 2: Update .env

```env
# POS Configuration
POS_CURRENCY=USD
POS_CURRENCY_SYMBOL=$
POS_TAX_RATE=0.00
POS_LOW_STOCK_THRESHOLD=10
POS_ORDER_PREFIX=POS-
POS_OFFLINE_MODE=true
POS_SYNC_INTERVAL=300

# WooCommerce
WC_STORE_URL=https://your-store.com
WC_CONSUMER_KEY=ck_xxxxx
WC_CONSUMER_SECRET=cs_xxxxx
```

---

## Part 7: Testing (Day 11-12)

### Step 1: Create Tests

```bash
php artisan make:test ProductTest
php artisan make:test OrderTest
php artisan make:test InventoryTest
```

**Example Test:**

```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'price' => 10.00,
            'type' => 'simple',
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
        ]);
    }
}
```

### Step 2: Run Tests

```bash
php artisan test
```

---

## Part 8: Deployment (Day 13-14)

Follow the detailed steps in [`Deployment_Hostinger_Guide.md`](Deployment_Hostinger_Guide.md).

**Quick Checklist:**
1. ✅ Optimize for production
2. ✅ Upload to Hostinger
3. ✅ Configure database
4. ✅ Set permissions
5. ✅ Run migrations
6. ✅ Test live system

---

## Common Commands Reference

```bash
# Development
php artisan serve                    # Start dev server
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed     # Fresh database with seeds
php artisan db:seed                  # Run seeders only

# Livewire
php artisan make:livewire ComponentName
php artisan livewire:publish --config

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# Queue
php artisan queue:work
php artisan queue:failed
php artisan queue:retry all

# Testing
php artisan test
php artisan test --filter ProductTest
```

---

## Troubleshooting

### Issue: Class not found
```bash
composer dump-autoload
```

### Issue: Permission denied
```bash
chmod -R 755 storage bootstrap/cache
```

### Issue: Migration error
```bash
php artisan migrate:fresh
```

### Issue: Livewire not working
```bash
php artisan livewire:publish --assets
```

---

## Next Steps

1. Complete all Livewire components
2. Implement WooCommerce sync
3. Add offline mode
4. Create reports
5. Deploy to production
6. Train users

Refer to [`Development_Roadmap.md`](Development_Roadmap.md) for detailed milestones.

---

## Getting Help

- Laravel Docs: https://laravel.com/docs
- Livewire Docs: https://livewire.laravel.com/docs
- WooCommerce API: https://woocommerce.github.io/woocommerce-rest-api-docs/
- Stack Overflow: Tag with `laravel`, `livewire`, `woocommerce`

---

## Conclusion

You now have a complete guide to build the POS system from scratch. Take it step by step, test frequently, and don't hesitate to refer back to the detailed documentation in the other planning files.

**Remember:** Start simple, get it working, then add features iteratively!