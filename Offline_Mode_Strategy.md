# Offline Mode Implementation Strategy

## 6. Offline Mode Strategy

### 6.1 Overview

The offline mode enables the POS system to continue operating when internet connectivity is lost, then automatically synchronize data when the connection is restored.

**Key Components:**
1. Service Worker for offline detection
2. IndexedDB for local data storage
3. Queue system for pending operations
4. Auto-sync mechanism
5. Conflict resolution

### 6.2 Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (POS Terminal)                │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  Livewire    │  │  Alpine.js   │  │   Service    │ │
│  │  Components  │  │              │  │   Worker     │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                              ↓          │
│  ┌──────────────────────────────────────────────────┐  │
│  │              IndexedDB (Local Storage)            │  │
│  │  - Products Cache                                 │  │
│  │  - Pending Orders                                 │  │
│  │  - Offline Queue                                  │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↕ (Online/Offline)
┌─────────────────────────────────────────────────────────┐
│                    Laravel Backend                       │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  Sync Queue  │  │   MySQL DB   │  │  WooCommerce │ │
│  │   Service    │  │              │  │     API      │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### 6.3 Service Worker Setup

```javascript
// public/service-worker.js
const CACHE_NAME = 'pos-cache-v1';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/offline.html',
];

// Install service worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
});

// Fetch with cache fallback
self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Clone response for cache
                const responseClone = response.clone();
                
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseClone);
                });
                
                return response;
            })
            .catch(() => {
                // Return cached version if offline
                return caches.match(event.request);
            })
    );
});

// Activate and clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
```

**Register Service Worker:**
```javascript
// resources/js/app.js
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then((registration) => {
                console.log('Service Worker registered:', registration);
            })
            .catch((error) => {
                console.log('Service Worker registration failed:', error);
            });
    });
}
```

### 6.4 IndexedDB Manager

```javascript
// resources/js/offline/indexed-db.js
class IndexedDBManager {
    constructor() {
        this.dbName = 'POSDatabase';
        this.version = 1;
        this.db = null;
    }
    
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Products store
                if (!db.objectStoreNames.contains('products')) {
                    const productsStore = db.createObjectStore('products', { keyPath: 'id' });
                    productsStore.createIndex('sku', 'sku', { unique: true });
                    productsStore.createIndex('barcode', 'barcode', { unique: false });
                }
                
                // Pending orders store
                if (!db.objectStoreNames.contains('pendingOrders')) {
                    const ordersStore = db.createObjectStore('pendingOrders', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    ordersStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
                
                // Sync queue store
                if (!db.objectStoreNames.contains('syncQueue')) {
                    const queueStore = db.createObjectStore('syncQueue', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    queueStore.createIndex('status', 'status', { unique: false });
                }
            };
        });
    }
    
    async addProduct(product) {
        const transaction = this.db.transaction(['products'], 'readwrite');
        const store = transaction.objectStore('products');
        return store.put(product);
    }
    
    async getProduct(id) {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');
        return new Promise((resolve, reject) => {
            const request = store.get(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async getProductByBarcode(barcode) {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');
        const index = store.index('barcode');
        
        return new Promise((resolve, reject) => {
            const request = index.get(barcode);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async getAllProducts() {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');
        
        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async addPendingOrder(order) {
        const transaction = this.db.transaction(['pendingOrders'], 'readwrite');
        const store = transaction.objectStore('pendingOrders');
        
        order.timestamp = Date.now();
        order.status = 'pending';
        
        return new Promise((resolve, reject) => {
            const request = store.add(order);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async getPendingOrders() {
        const transaction = this.db.transaction(['pendingOrders'], 'readonly');
        const store = transaction.objectStore('pendingOrders');
        
        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async removePendingOrder(id) {
        const transaction = this.db.transaction(['pendingOrders'], 'readwrite');
        const store = transaction.objectStore('pendingOrders');
        return store.delete(id);
    }
    
    async addToSyncQueue(item) {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');
        
        item.timestamp = Date.now();
        item.status = 'pending';
        item.attempts = 0;
        
        return store.add(item);
    }
    
    async getSyncQueue() {
        const transaction = this.db.transaction(['syncQueue'], 'readonly');
        const store = transaction.objectStore('syncQueue');
        const index = store.index('status');
        
        return new Promise((resolve, reject) => {
            const request = index.getAll('pending');
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async updateSyncQueueItem(id, updates) {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');
        
        return new Promise((resolve, reject) => {
            const getRequest = store.get(id);
            
            getRequest.onsuccess = () => {
                const item = getRequest.result;
                Object.assign(item, updates);
                
                const putRequest = store.put(item);
                putRequest.onsuccess = () => resolve(putRequest.result);
                putRequest.onerror = () => reject(putRequest.error);
            };
            
            getRequest.onerror = () => reject(getRequest.error);
        });
    }
    
    async clearSyncQueue() {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');
        return store.clear();
    }
}

// Export singleton instance
window.dbManager = new IndexedDBManager();
```

### 6.5 Offline Detection & Status

```javascript
// resources/js/offline/connection-monitor.js
document.addEventListener('alpine:init', () => {
    Alpine.data('connectionMonitor', () => ({
        isOnline: navigator.onLine,
        lastSync: null,
        pendingCount: 0,
        
        init() {
            this.checkConnection();
            this.loadPendingCount();
            
            // Listen for online/offline events
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.handleOnline();
            });
            
            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.handleOffline();
            });
            
            // Periodic connection check
            setInterval(() => {
                this.checkConnection();
            }, 30000); // Every 30 seconds
        },
        
        async checkConnection() {
            if (!navigator.onLine) {
                this.isOnline = false;
                return;
            }
            
            try {
                const response = await fetch('/api/ping', {
                    method: 'HEAD',
                    cache: 'no-cache'
                });
                
                this.isOnline = response.ok;
            } catch (error) {
                this.isOnline = false;
            }
        },
        
        async handleOnline() {
            console.log('Connection restored');
            
            // Show notification
            this.$dispatch('notify', {
                type: 'success',
                message: 'Connection restored. Syncing data...'
            });
            
            // Trigger sync
            await this.syncPendingData();
        },
        
        handleOffline() {
            console.log('Connection lost');
            
            // Show notification
            this.$dispatch('notify', {
                type: 'warning',
                message: 'Working offline. Data will sync when connection is restored.'
            });
        },
        
        async syncPendingData() {
            const pendingOrders = await window.dbManager.getPendingOrders();
            
            for (const order of pendingOrders) {
                try {
                    const response = await fetch('/api/orders/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(order)
                    });
                    
                    if (response.ok) {
                        await window.dbManager.removePendingOrder(order.id);
                        this.pendingCount--;
                    }
                } catch (error) {
                    console.error('Sync failed:', error);
                }
            }
            
            this.lastSync = new Date();
            
            // Refresh product cache
            await this.refreshProductCache();
        },
        
        async refreshProductCache() {
            try {
                const response = await fetch('/api/products/cache');
                const products = await response.json();
                
                for (const product of products) {
                    await window.dbManager.addProduct(product);
                }
            } catch (error) {
                console.error('Cache refresh failed:', error);
            }
        },
        
        async loadPendingCount() {
            const pending = await window.dbManager.getPendingOrders();
            this.pendingCount = pending.length;
        }
    }));
});
```

### 6.6 Offline-Capable POS Terminal

```php
// app/Livewire/Pos/OfflinePosTerminal.php
class OfflinePosTerminal extends Component
{
    public $cart = [];
    public $isOnline = true;
    
    protected $listeners = [
        'productScanned' => 'addToCart',
        'connectionStatusChanged' => 'updateConnectionStatus',
    ];
    
    public function addToCart($productId, $quantity = 1)
    {
        // Try to get from server first
        if ($this->isOnline) {
            try {
                $product = Product::with('inventory')->find($productId);
            } catch (\Exception $e) {
                // Fallback to cached data
                $this->dispatch('getProductFromCache', ['productId' => $productId]);
                return;
            }
        } else {
            // Use cached data
            $this->dispatch('getProductFromCache', ['productId' => $productId]);
            return;
        }
        
        // Add to cart logic...
    }
    
    public function completeOrder()
    {
        if ($this->isOnline) {
            // Normal online checkout
            return $this->processOnlineOrder();
        } else {
            // Offline checkout
            return $this->processOfflineOrder();
        }
    }
    
    protected function processOfflineOrder()
    {
        $orderData = [
            'order_number' => $this->generateOfflineOrderNumber(),
            'customer_id' => $this->customer?->id,
            'user_id' => auth()->id(),
            'cart' => $this->cart,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'total' => $this->total,
            'payments' => $this->payments,
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Store in IndexedDB
        $this->dispatch('storeOfflineOrder', ['order' => $orderData]);
        
        // Generate offline receipt
        return redirect()->route('pos.offline-receipt', [
            'orderNumber' => $orderData['order_number']
        ]);
    }
    
    protected function generateOfflineOrderNumber()
    {
        $prefix = 'OFFLINE-';
        $timestamp = now()->format('YmdHis');
        $random = substr(md5(uniqid()), 0, 4);
        
        return $prefix . $timestamp . '-' . $random;
    }
    
    public function updateConnectionStatus($isOnline)
    {
        $this->isOnline = $isOnline;
    }
}
```

### 6.7 Sync Service (Backend)

```php
// app/Services/OfflineSyncService.php
class OfflineSyncService
{
    public function syncOfflineOrder($orderData)
    {
        DB::beginTransaction();
        
        try {
            // Create order
            $order = Order::create([
                'order_number' => $this->generateNewOrderNumber($orderData['order_number']),
                'customer_id' => $orderData['customer_id'],
                'user_id' => $orderData['user_id'],
                'status' => 'completed',
                'subtotal' => $orderData['subtotal'],
                'tax_amount' => $orderData['tax'],
                'total' => $orderData['total'],
                'payment_status' => 'paid',
                'notes' => 'Synced from offline order: ' . $orderData['order_number'],
            ]);
            
            // Create order items
            foreach ($orderData['cart'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax_rate' => $item['tax_rate'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'total' => $item['price'] * $item['quantity'] * (1 + $item['tax_rate'] / 100),
                ]);
                
                // Update inventory
                $product = Product::find($item['product_id']);
                if ($product && $product->track_inventory) {
                    $product->inventory->decrement('quantity', $item['quantity']);
                    
                    StockMovement::create([
                        'inventoriable_type' => Product::class,
                        'inventoriable_id' => $product->id,
                        'type' => 'sale',
                        'quantity' => -$item['quantity'],
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'user_id' => $orderData['user_id'],
                        'notes' => 'Offline order sync',
                    ]);
                }
            }
            
            // Create payments
            foreach ($orderData['payments'] as $payment) {
                $order->payments()->create([
                    'payment_method' => $payment['method'],
                    'amount' => $payment['amount'],
                ]);
            }
            
            // Queue for WooCommerce sync
            SyncQueue::create([
                'syncable_type' => Order::class,
                'syncable_id' => $order->id,
                'action' => 'create',
                'status' => 'pending',
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Offline order sync failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function generateNewOrderNumber($offlineNumber)
    {
        // Convert offline order number to regular format
        $prefix = config('pos.order_number_prefix', 'POS-');
        $date = now()->format('Ymd');
        $sequence = Order::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    public function getProductCache()
    {
        // Return essential product data for offline use
        return Product::with(['inventory', 'barcodes'])
            ->where('is_active', true)
            ->select([
                'id', 'sku', 'name', 'price', 'tax_rate', 
                'type', 'image_url', 'track_inventory'
            ])
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'tax_rate' => $product->tax_rate,
                    'type' => $product->type,
                    'image_url' => $product->image_url,
                    'track_inventory' => $product->track_inventory,
                    'quantity' => $product->inventory?->quantity ?? 0,
                    'barcode' => $product->barcodes->first()?->barcode,
                ];
            });
    }
}
```

### 6.8 API Routes for Offline Sync

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Connection check
    Route::head('/ping', function () {
        return response()->json(['status' => 'ok']);
    });
    
    // Product cache for offline mode
    Route::get('/products/cache', function (OfflineSyncService $syncService) {
        return response()->json($syncService->getProductCache());
    });
    
    // Sync offline orders
    Route::post('/orders/sync', function (Request $request, OfflineSyncService $syncService) {
        $result = $syncService->syncOfflineOrder($request->all());
        
        return response()->json($result, $result['success'] ? 200 : 500);
    });
    
    // Get product by barcode (offline fallback)
    Route::get('/products/by-barcode/{barcode}', function ($barcode) {
        $product = Product::whereHas('barcodes', function($query) use ($barcode) {
            $query->where('barcode', $barcode);
        })->with('inventory')->first();
        
        return response()->json(['product' => $product]);
    });
});
```

### 6.9 Offline Receipt Template

```blade
<!-- resources/views/pos/offline-receipt.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Offline Receipt</title>
    <style>
        body { font-family: monospace; max-width: 300px; margin: 20px auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .offline-notice { 
            background: #fff3cd; 
            padding: 10px; 
            border: 1px solid #ffc107;
            margin-bottom: 20px;
            text-align: center;
        }
        .items { margin: 20px 0; }
        .item { display: flex; justify-content: space-between; margin: 5px 0; }
        .totals { border-top: 2px solid #000; padding-top: 10px; }
        .total-line { display: flex; justify-content: space-between; margin: 5px 0; }
        .grand-total { font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <p>OFFLINE RECEIPT</p>
        <p>{{ $orderNumber }}</p>
        <p>{{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
    
    <div class="offline-notice">
        ⚠️ This order was processed offline<br>
        Will sync when connection is restored
    </div>
    
    <div class="items">
        <!-- Items will be populated by JavaScript -->
    </div>
    
    <div class="totals">
        <div class="total-line">
            <span>Subtotal:</span>
            <span id="subtotal"></span>
        </div>
        <div class="total-line">
            <span>Tax:</span>
            <span id="tax"></span>
        </div>
        <div class="total-line grand-total">
            <span>TOTAL:</span>
            <span id="total"></span>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html>
```

### 6.10 Conflict Resolution Strategy

**Inventory Conflicts:**
```php
// app/Services/ConflictResolutionService.php
class ConflictResolutionService
{
    public function resolveInventoryConflict($product, $offlineQuantity, $onlineQuantity)
    {
        // Strategy: Use the lower quantity to prevent overselling
        $resolvedQuantity = min($offlineQuantity, $onlineQuantity);
        
        $product->inventory->update(['quantity' => $resolvedQuantity]);
        
        // Log the conflict
        \Log::warning('Inventory conflict resolved', [
            'product_id' => $product->id,
            'offline_quantity' => $offlineQuantity,
            'online_quantity' => $onlineQuantity,
            'resolved_quantity' => $resolvedQuantity,
        ]);
        
        return $resolvedQuantity;
    }
}
```

### 6.11 Testing Offline Mode

**Manual Testing Steps:**
1. Open POS terminal
2. Disable network in browser DevTools (Network tab → Offline)
3. Scan products and create order
4. Complete checkout
5. Verify order stored in IndexedDB
6. Re-enable network
7. Verify automatic sync
8. Check order appears in database

**Automated Test:**
```php
// tests/Feature/OfflineModeTest.php
class OfflineModeTest extends TestCase
{
    public function test_offline_order_can_be_synced()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);
        
        $offlineOrder = [
            'order_number' => 'OFFLINE-20240101120000-abcd',
            'user_id' => $user->id,
            'cart' => [
                [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => 2,
                    'price' => 10.00,
                    'tax_rate' => 0,
                ]
            ],
            'subtotal' => 20.00,
            'tax' => 0,
            'total' => 20.00,
            'payments' => [
                ['method' => 'cash', 'amount' => 20.00]
            ],
        ];
        
        $response = $this->actingAs($user)
            ->postJson('/api/orders/sync', $offlineOrder);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total' => 20.00,
        ]);
    }
}
```

### 6.12 Offline Mode Best Practices

1. **Cache Essential Data:**
   - Active products only
   - Current inventory levels
   - Customer list (optional)

2. **Limit Offline Duration:**
   - Show warning after 1 hour offline
   - Recommend syncing before end of shift

3. **User Notifications:**
   - Clear offline indicator
   - Pending sync count
   - Last sync timestamp

4. **Data Validation:**
   - Validate offline orders before sync
   - Check inventory availability
   - Handle conflicts gracefully

5. **Error Handling:**
   - Retry failed syncs
   - Log all sync attempts
   - Alert manager of persistent failures