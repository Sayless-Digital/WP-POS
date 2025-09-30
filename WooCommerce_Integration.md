# WooCommerce Integration Strategy

## 5. WooCommerce Integration

### 5.1 Authentication Setup

**WooCommerce REST API Configuration:**
```php
// config/woocommerce.php
return [
    'store_url' => env('WC_STORE_URL'),
    'consumer_key' => env('WC_CONSUMER_KEY'),
    'consumer_secret' => env('WC_CONSUMER_SECRET'),
    'api_version' => 'wc/v3',
    'verify_ssl' => true,
    'timeout' => 30,
];
```

**Environment Variables:**
```env
WC_STORE_URL=https://your-store.com
WC_CONSUMER_KEY=ck_xxxxxxxxxxxxx
WC_CONSUMER_SECRET=cs_xxxxxxxxxxxxx
```

**Generate API Keys in WooCommerce:**
1. Go to WooCommerce → Settings → Advanced → REST API
2. Click "Add Key"
3. Description: "POS System"
4. User: Select admin user
5. Permissions: Read/Write
6. Click "Generate API Key"
7. Copy Consumer Key and Consumer Secret to `.env`

### 5.2 WooCommerce Client Service

```php
// app/Services/WooCommerce/WooCommerceClient.php
use Automattic\WooCommerce\Client;

class WooCommerceClient
{
    protected $client;
    
    public function __construct()
    {
        $this->client = new Client(
            config('woocommerce.store_url'),
            config('woocommerce.consumer_key'),
            config('woocommerce.consumer_secret'),
            [
                'version' => config('woocommerce.api_version'),
                'timeout' => config('woocommerce.timeout'),
            ]
        );
    }
    
    public function get($endpoint, $params = [])
    {
        try {
            return $this->client->get($endpoint, $params);
        } catch (\Exception $e) {
            \Log::error('WooCommerce API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function post($endpoint, $data)
    {
        try {
            return $this->client->post($endpoint, $data);
        } catch (\Exception $e) {
            \Log::error('WooCommerce API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function put($endpoint, $data)
    {
        try {
            return $this->client->put($endpoint, $data);
        } catch (\Exception $e) {
            \Log::error('WooCommerce API Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
```

**Install WooCommerce SDK:**
```bash
composer require automattic/woocommerce
```

### 5.3 Product Sync Service

```php
// app/Services/WooCommerce/ProductSyncService.php
class ProductSyncService
{
    protected $client;
    
    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }
    
    public function importProducts($page = 1, $perPage = 100)
    {
        $products = $this->client->get('products', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
        
        foreach ($products as $wcProduct) {
            $this->importProduct($wcProduct);
        }
        
        return count($products);
    }
    
    protected function importProduct($wcProduct)
    {
        DB::beginTransaction();
        
        try {
            $product = Product::updateOrCreate(
                ['woocommerce_id' => $wcProduct->id],
                [
                    'sku' => $wcProduct->sku,
                    'name' => $wcProduct->name,
                    'description' => $wcProduct->description,
                    'type' => $wcProduct->type,
                    'price' => $wcProduct->price,
                    'tax_rate' => $this->calculateTaxRate($wcProduct),
                    'is_active' => $wcProduct->status === 'publish',
                    'image_url' => $wcProduct->images[0]->src ?? null,
                    'synced_at' => now(),
                ]
            );
            
            // Import variants for variable products
            if ($wcProduct->type === 'variable') {
                $this->importVariants($product, $wcProduct->id);
            }
            
            // Update inventory
            if ($wcProduct->manage_stock) {
                Inventory::updateOrCreate(
                    [
                        'inventoriable_type' => Product::class,
                        'inventoriable_id' => $product->id,
                    ],
                    [
                        'quantity' => $wcProduct->stock_quantity ?? 0,
                    ]
                );
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product import failed: ' . $e->getMessage());
        }
    }
    
    protected function importVariants($product, $wcProductId)
    {
        $variations = $this->client->get("products/{$wcProductId}/variations");
        
        foreach ($variations as $wcVariation) {
            $variant = ProductVariant::updateOrCreate(
                ['woocommerce_id' => $wcVariation->id],
                [
                    'product_id' => $product->id,
                    'sku' => $wcVariation->sku,
                    'name' => implode(', ', array_column($wcVariation->attributes, 'option')),
                    'attributes' => json_encode($wcVariation->attributes),
                    'price' => $wcVariation->price,
                    'is_active' => $wcVariation->status === 'publish',
                ]
            );
            
            // Update variant inventory
            if ($wcVariation->manage_stock) {
                Inventory::updateOrCreate(
                    [
                        'inventoriable_type' => ProductVariant::class,
                        'inventoriable_id' => $variant->id,
                    ],
                    [
                        'quantity' => $wcVariation->stock_quantity ?? 0,
                    ]
                );
            }
        }
    }
    
    public function exportInventoryUpdate($product)
    {
        if (!$product->woocommerce_id) {
            return;
        }
        
        $inventory = $product->inventory;
        
        $this->client->put("products/{$product->woocommerce_id}", [
            'stock_quantity' => $inventory->quantity,
        ]);
    }
    
    protected function calculateTaxRate($wcProduct)
    {
        // WooCommerce tax is complex, simplify for POS
        if ($wcProduct->tax_status === 'taxable' && !empty($wcProduct->tax_class)) {
            return config('pos.tax_rate', 0);
        }
        return 0;
    }
}
```

### 5.4 Order Sync Service

```php
// app/Services/WooCommerce/OrderSyncService.php
class OrderSyncService
{
    protected $client;
    
    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }
    
    public function exportOrder($order)
    {
        $orderData = [
            'status' => 'completed',
            'customer_id' => $order->customer?->woocommerce_id,
            'payment_method' => 'pos',
            'payment_method_title' => 'POS Payment',
            'set_paid' => true,
            'billing' => $this->getBillingData($order->customer),
            'line_items' => $this->getLineItems($order),
            'meta_data' => [
                [
                    'key' => '_pos_order_id',
                    'value' => $order->id,
                ],
                [
                    'key' => '_pos_order_number',
                    'value' => $order->order_number,
                ],
                [
                    'key' => '_pos_cashier',
                    'value' => $order->user->name,
                ],
            ],
        ];
        
        $wcOrder = $this->client->post('orders', $orderData);
        
        $order->update([
            'woocommerce_id' => $wcOrder->id,
            'is_synced' => true,
            'synced_at' => now(),
        ]);
        
        return $wcOrder;
    }
    
    protected function getLineItems($order)
    {
        return $order->items->map(function($item) {
            return [
                'product_id' => $item->product?->woocommerce_id,
                'variation_id' => $item->variant?->woocommerce_id,
                'quantity' => $item->quantity,
                'subtotal' => (string) $item->subtotal,
                'total' => (string) $item->total,
            ];
        })->toArray();
    }
    
    protected function getBillingData($customer)
    {
        if (!$customer) {
            return [];
        }
        
        return [
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address_1' => $customer->address,
            'city' => $customer->city,
            'postcode' => $customer->postal_code,
        ];
    }
}
```

### 5.5 Customer Sync Service

```php
// app/Services/WooCommerce/CustomerSyncService.php
class CustomerSyncService
{
    protected $client;
    
    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }
    
    public function importCustomers($page = 1, $perPage = 100)
    {
        $customers = $this->client->get('customers', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
        
        foreach ($customers as $wcCustomer) {
            $this->importCustomer($wcCustomer);
        }
        
        return count($customers);
    }
    
    protected function importCustomer($wcCustomer)
    {
        Customer::updateOrCreate(
            ['woocommerce_id' => $wcCustomer->id],
            [
                'first_name' => $wcCustomer->first_name,
                'last_name' => $wcCustomer->last_name,
                'email' => $wcCustomer->email,
                'phone' => $wcCustomer->billing->phone ?? null,
                'address' => $wcCustomer->billing->address_1 ?? null,
                'city' => $wcCustomer->billing->city ?? null,
                'postal_code' => $wcCustomer->billing->postcode ?? null,
                'synced_at' => now(),
            ]
        );
    }
    
    public function exportCustomer($customer)
    {
        if ($customer->woocommerce_id) {
            // Update existing
            $this->client->put("customers/{$customer->woocommerce_id}", [
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'billing' => [
                    'phone' => $customer->phone,
                    'address_1' => $customer->address,
                    'city' => $customer->city,
                    'postcode' => $customer->postal_code,
                ],
            ]);
        } else {
            // Create new
            $wcCustomer = $this->client->post('customers', [
                'email' => $customer->email,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'billing' => [
                    'phone' => $customer->phone,
                    'address_1' => $customer->address,
                    'city' => $customer->city,
                    'postcode' => $customer->postal_code,
                ],
            ]);
            
            $customer->update([
                'woocommerce_id' => $wcCustomer->id,
                'synced_at' => now(),
            ]);
        }
    }
}
```

### 5.6 Sync Jobs (Background Processing)

```php
// app/Jobs/SyncProductsFromWooCommerce.php
class SyncProductsFromWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(ProductSyncService $syncService)
    {
        $page = 1;
        $hasMore = true;
        
        SyncLog::create([
            'type' => 'products',
            'direction' => 'import',
            'status' => 'success',
            'started_at' => now(),
        ]);
        
        try {
            while ($hasMore) {
                $count = $syncService->importProducts($page, 100);
                
                if ($count < 100) {
                    $hasMore = false;
                }
                
                $page++;
            }
            
            SyncLog::where('type', 'products')
                ->latest()
                ->first()
                ->update([
                    'completed_at' => now(),
                    'records_processed' => Product::count(),
                ]);
                
        } catch (\Exception $e) {
            SyncLog::where('type', 'products')
                ->latest()
                ->first()
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
        }
    }
}
```

```php
// app/Jobs/SyncOrderToWooCommerce.php
class SyncOrderToWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $order;
    
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    
    public function handle(OrderSyncService $syncService)
    {
        try {
            $syncService->exportOrder($this->order);
        } catch (\Exception $e) {
            \Log::error('Order sync failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}
```

### 5.7 Sync Command (Manual Trigger)

```php
// app/Console/Commands/SyncWooCommerce.php
class SyncWooCommerce extends Command
{
    protected $signature = 'woocommerce:sync {type=all}';
    protected $description = 'Sync data with WooCommerce';
    
    public function handle()
    {
        $type = $this->argument('type');
        
        if ($type === 'all' || $type === 'products') {
            $this->info('Syncing products...');
            SyncProductsFromWooCommerce::dispatch();
        }
        
        if ($type === 'all' || $type === 'customers') {
            $this->info('Syncing customers...');
            SyncCustomersFromWooCommerce::dispatch();
        }
        
        if ($type === 'all' || $type === 'orders') {
            $this->info('Syncing pending orders...');
            $pendingOrders = Order::where('is_synced', false)->get();
            
            foreach ($pendingOrders as $order) {
                SyncOrderToWooCommerce::dispatch($order);
            }
        }
        
        $this->info('Sync jobs dispatched!');
    }
}
```

### 5.8 Automatic Sync Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Sync products every 6 hours
    $schedule->job(new SyncProductsFromWooCommerce())
        ->everySixHours();
    
    // Sync pending orders every 5 minutes
    $schedule->call(function () {
        $pendingOrders = Order::where('is_synced', false)
            ->where('created_at', '>', now()->subHours(24))
            ->get();
            
        foreach ($pendingOrders as $order) {
            SyncOrderToWooCommerce::dispatch($order);
        }
    })->everyFiveMinutes();
    
    // Sync inventory updates every 15 minutes
    $schedule->call(function () {
        $products = Product::whereHas('inventory', function($query) {
            $query->where('updated_at', '>', now()->subMinutes(15));
        })->get();
        
        foreach ($products as $product) {
            SyncInventoryToWooCommerce::dispatch($product);
        }
    })->everyFifteenMinutes();
}
```

### 5.9 Webhook Handlers (Optional - Real-time Sync)

```php
// app/Http/Controllers/WooCommerceWebhookController.php
class WooCommerceWebhookController extends Controller
{
    public function handleProductUpdate(Request $request)
    {
        $wcProduct = $request->all();
        
        app(ProductSyncService::class)->importProduct((object) $wcProduct);
        
        return response()->json(['status' => 'success']);
    }
    
    public function handleOrderCreate(Request $request)
    {
        // Handle online orders if needed
        $wcOrder = $request->all();
        
        // Import to POS for reference
        // ...
        
        return response()->json(['status' => 'success']);
    }
}
```

**Register Webhook Routes:**
```php
// routes/api.php
Route::post('/webhooks/woocommerce/product', [WooCommerceWebhookController::class, 'handleProductUpdate']);
Route::post('/webhooks/woocommerce/order', [WooCommerceWebhookController::class, 'handleOrderCreate']);
```

### 5.10 Sync Management UI

```php
// app/Livewire/Admin/SyncManager.php
class SyncManager extends Component
{
    public $syncLogs;
    
    public function mount()
    {
        $this->loadLogs();
    }
    
    public function loadLogs()
    {
        $this->syncLogs = SyncLog::orderBy('created_at', 'desc')
            ->take(20)
            ->get();
    }
    
    public function syncProducts()
    {
        SyncProductsFromWooCommerce::dispatch();
        $this->dispatch('success', 'Product sync started');
    }
    
    public function syncOrders()
    {
        $pendingOrders = Order::where('is_synced', false)->get();
        
        foreach ($pendingOrders as $order) {
            SyncOrderToWooCommerce::dispatch($order);
        }
        
        $this->dispatch('success', count($pendingOrders) . ' orders queued for sync');
    }
    
    public function render()
    {
        return view('livewire.admin.sync-manager');
    }
}
```

### 5.11 Sync Strategy Summary

**Data Flow:**

```
WooCommerce → POS (Import):
- Products (scheduled every 6 hours)
- Product variants
- Categories
- Customers (on-demand)

POS → WooCommerce (Export):
- Orders (real-time after checkout)
- Inventory updates (every 15 minutes)
- New customers (on-demand)

Conflict Resolution:
- WooCommerce is the master for products
- POS is the master for inventory
- Orders created in POS sync to WooCommerce
- Timestamps track last sync
```

**Error Handling:**
- Failed syncs logged to `sync_logs` table
- Retry mechanism with exponential backoff
- Manual retry option in admin UI
- Email notifications for critical failures