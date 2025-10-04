
# WordPress POS - Offline Sync Architecture

**Version:** 1.0  
**Date:** 2025-10-04  
**Requirements:** Maximum offline capability, aggressive sync, conflict prevention

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Architecture Overview](#system-architecture-overview)
3. [IndexedDB Schema Design](#indexeddb-schema-design)
4. [Service Worker Strategy](#service-worker-strategy)
5. [Sync Queue Mechanism](#sync-queue-mechanism)
6. [Conflict Prevention Strategy](#conflict-prevention-strategy)
7. [Integration with Existing Code](#integration-with-existing-code)
8. [API Modifications](#api-modifications)
9. [File Structure & Organization](#file-structure--organization)
10. [Data Flow Diagrams](#data-flow-diagrams)
11. [Implementation Phases](#implementation-phases)
12. [Performance Considerations](#performance-considerations)

---

## 1. Executive Summary

This document outlines a comprehensive offline-first architecture for the WordPress/WooCommerce POS system. The solution provides:

- **Full offline operation** for products catalog, cart management, and order creation
- **Automatic background sync** when connection is restored + periodic sync when online
- **Conflict prevention** through optimistic locking and session tokens
- **Progressive Web App (PWA)** capabilities via Service Workers
- **Seamless integration** with existing [`appState`](../assets/js/main.js:8) structure and API endpoints

### Key Features

✅ **Offline-First Design**: All critical operations work without internet  
✅ **Smart Caching**: Service Worker caches static assets + API responses  
✅ **Background Sync**: Automatic queue processing when online  
✅ **Visual Indicators**: Clear online/offline status in UI  
✅ **Data Integrity**: Conflict prevention and server-authoritative resolution  

---

## 2. System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress POS Application                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐     │
│  │   UI Layer   │◄──►│   appState   │◄──►│ SyncManager  │     │
│  │ (main.js)    │    │  (existing)  │    │   (new)      │     │
│  └──────────────┘    └──────────────┘    └──────┬───────┘     │
│         │                    │                    │              │
│         │                    │                    │              │
│  ┌──────▼────────────────────▼────────────────────▼───────┐    │
│  │           OfflineDataManager (new)                      │    │
│  │  - Handles IndexedDB operations                         │    │
│  │  - Manages sync queue                                   │    │
│  │  - Coordinates online/offline transitions               │    │
│  └──────┬──────────────────────────────────────────────────┘   │
│         │                                                        │
│  ┌──────▼────────────────────────────────────────────────┐     │
│  │              IndexedDB Stores                          │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐           │     │
│  │  │ Products │  │  Orders  │  │SyncQueue │           │     │
│  │  └──────────┘  └──────────┘  └──────────┘           │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐           │     │
│  │  │   Cart   │  │ Settings │  │Metadata  │           │     │
│  │  └──────────┘  └──────────┘  └──────────┘           │     │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
└──────────────────────────────┬───────────────────────────────────┘
                               │
                    ┌──────────▼──────────┐
                    │   Service Worker    │
                    │  (sw.js - new)      │
                    │                     │
                    │  - Cache static     │
                    │  - Cache API resp   │
                    │  - Background sync  │
                    │  - Push notif       │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │   Network Layer     │
                    ├─────────────────────┤
                    │  WordPress APIs     │
                    │  - products.php     │
                    │  - checkout.php     │
                    │  - orders.php       │
                    │  - (modified)       │
                    └─────────────────────┘
```

### Architecture Principles

1. **Progressive Enhancement**: Core functionality works offline, enhanced when online
2. **Server Authoritative**: Server is source of truth, client syncs to server state
3. **Optimistic Updates**: UI updates immediately, sync happens in background
4. **Graceful Degradation**: Features degrade gracefully when offline

---

## 3. IndexedDB Schema Design

### Database Configuration

```javascript
// Database Name: 'jpos-offline-db'
// Version: 1
// Stores: 6 (products, orders, cart, syncQueue, settings, metadata)
```

### Store Schemas

#### 3.1 Products Store

```javascript
{
  name: 'products',
  keyPath: 'id',
  indexes: [
    { name: 'sku', keyPath: 'sku', unique: true },
    { name: 'stock_status', keyPath: 'stock_status', unique: false },
    { name: 'category_ids', keyPath: 'category_ids', unique: false, multiEntry: true },
    { name: 'tag_ids', keyPath: 'tag_ids', unique: false, multiEntry: true },
    { name: 'updated_at', keyPath: 'updated_at', unique: false }
  ],
  schema: {
    id: 'number',               // Primary key
    name: 'string',
    sku: 'string',
    price: 'number',
    stock_status: 'string',     // 'instock' | 'outofstock'
    manages_stock: 'boolean',
    stock_quantity: 'number?',
    type: 'string',             // 'simple' | 'variable'
    image_url: 'string',
    category_ids: 'number[]',
    tag_ids: 'number[]',
    variations: 'Variation[]',  // For variable products
    post_status: 'string',
    min_price: 'number?',
    updated_at: 'number',       // Timestamp for sync comparison
    sync_version: 'number'      // Optimistic locking version
  }
}
```

#### 3.2 Orders Store

```javascript
{
  name: 'orders',
  keyPath: 'local_id',
  indexes: [
    { name: 'order_number', keyPath: 'order_number', unique: true },
    { name: 'status', keyPath: 'status', unique: false },
    { name: 'date_created', keyPath: 'date_created', unique: false },
    { name: 'sync_status', keyPath: 'sync_status', unique: false }
  ],
  schema: {
    local_id: 'string',         // UUID generated client-side
    order_number: 'string?',    // Assigned by server after sync
    server_id: 'number?',       // WooCommerce order ID
    date_created: 'string',     // ISO 8601 timestamp
    status: 'string',           // 'pending' | 'completed' | 'failed'
    sync_status: 'string',      // 'pending' | 'syncing' | 'synced' | 'error'
    items: 'OrderItem[]',
    payment_method: 'string',
    split_payments: 'SplitPayment[]?',
    subtotal: 'number',
    total: 'number',
    fee: 'FeeDiscount?',
    discount: 'FeeDiscount?',
    customer: 'Customer',
    created_at: 'number',       // Local timestamp
    synced_at: 'number?',       // Sync timestamp
    retry_count: 'number',      // For failed syncs
    error_message: 'string?'
  }
}
```

#### 3.3 Cart Store

```javascript
{
  name: 'cart',
  keyPath: 'id',
  schema: {
    id: 'string',               // Always 'current' for active cart
    items: 'CartItem[]',
    paymentMethod: 'string',
    fee: 'FeeDiscount',
    discount: 'FeeDiscount',
    splitPayments: 'SplitPayment[]?',
    updated_at: 'number',
    session_id: 'string'        // For conflict prevention
  }
}
```

#### 3.4 Sync Queue Store

```javascript
{
  name: 'syncQueue',
  keyPath: 'id',
  indexes: [
    { name: 'priority', keyPath: 'priority', unique: false },
    { name: 'created_at', keyPath: 'created_at', unique: false },
    { name: 'status', keyPath: 'status', unique: false }
  ],
  schema: {
    id: 'string',               // UUID
    type: 'string',             // 'order' | 'product_update' | 'stock_update'
    priority: 'number',         // 1 (high) to 10 (low)
    payload: 'any',             // Operation-specific data
    endpoint: 'string',         // API endpoint to call
    method: 'string',           // 'POST' | 'PUT' | 'PATCH'
    status: 'string',           // 'pending' | 'processing' | 'completed' | 'failed'
    retry_count: 'number',
    max_retries: 'number',
    created_at: 'number',
    processed_at: 'number?',
    error: 'string?',
    lock_token: 'string?'       // For conflict prevention
  }
}
```

#### 3.5 Settings Store

```javascript
{
  name: 'settings',
  keyPath: 'key',
  schema: {
    key: 'string',              // 'receipt' | 'session' | 'preferences'
    value: 'any',
    updated_at: 'number',
    sync_status: 'string'
  }
}
```

#### 3.6 Metadata Store

```javascript
{
  name: 'metadata',
  keyPath: 'key',
  schema: {
    key: 'string',              // 'last_sync' | 'db_version' | 'session_id'
    value: 'any',
    updated_at: 'number'
  }
}
```

### Data Type Definitions

```typescript
interface Variation {
  id: number;
  parent_id: number;
  sku: string;
  price: number;
  stock_status: string;
  manages_stock: boolean;
  stock_quantity: number | null;
  attributes: Record<string, string>;
  image_url: string;
}

interface OrderItem {
  id: number;
  name: string;
  sku: string;
  quantity: number;
  price: number;
  total: number;
}

interface SplitPayment {
  method: string;
  amount: number;
}

interface FeeDiscount {
  type: 'fee' | 'discount';
  amount: string;
  label: string;
  amountType: 'flat' | 'percentage';
}

interface Customer {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
}

interface CartItem {
  id: number;
  name: string;
  sku: string;
  price: number;
  qty: number;
  image_url: string;
}
```

---

## 4. Service Worker Strategy

### 4.1 Service Worker Lifecycle

```javascript
// sw.js - Service Worker entry point

const CACHE_VERSION = 'jpos-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const API_CACHE = `${CACHE_VERSION}-api`;
const IMAGE_CACHE = `${CACHE_VERSION}-images`;

// Resources to cache on install
const STATIC_ASSETS = [
  '/jpos/',
  '/jpos/index.php',
  '/jpos/assets/js/main.js',
  '/jpos/assets/js/modules/routing.js',
  '/jpos/assets/css/styles.css',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/chart.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'
];

// API endpoints to cache with strategies
const API_CACHE_STRATEGIES = {
  '/jpos/api/products.php': 'cache-first',      // Products rarely change
  '/jpos/api/settings.php': 'cache-first',      // Settings rarely change
  '/jpos/api/checkout.php': 'network-only',     // Must be fresh
  '/jpos/api/orders.php': 'network-first',      // Fresh if possible
  '/jpos/api/stock.php': 'network-first'        // Fresh if possible
};
```

### 4.2 Caching Strategies

#### Cache-First (for products, images, static assets)
```
1. Check cache first
2. Return cached version if available
3. Fetch from network in background (update cache)
4. If offline and not in cache, return fallback
```

#### Network-First (for orders, stock updates)
```
1. Try network first
2. If network fails, check cache
3. If online, update cache with response
4. If offline, queue for sync
```

#### Network-Only (for checkout, auth)
```
1. Always fetch from network
2. Never cache these requests
3. If offline, add to sync queue
```

### 4.3 Background Sync

```javascript
// Register background sync
self.addEventListener('sync', event => {
  if (event.tag === 'sync-orders') {
    event.waitUntil(syncOrders());
  }
  if (event.tag === 'sync-products') {
    event.waitUntil(syncProducts());
  }
});

// Periodic background sync (every 5 minutes when online)
self.addEventListener('periodicsync', event => {
  if (event.tag === 'periodic-sync') {
    event.waitUntil(performPeriodicSync());
  }
});
```

### 4.4 Service Worker Registration

```javascript
// In main.js - Register Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register('/jpos/sw.js', {
        scope: '/jpos/'
      });
      
      console.log('Service Worker registered:', registration);
      
      // Request periodic sync permission
      if ('periodicSync' in registration) {
        await registration.periodicSync.register('periodic-sync', {
          minInterval: 5 * 60 * 1000 // 5 minutes
        });
      }
    } catch (error) {
      console.error('Service Worker registration failed:', error);
    }
  });
}
```

---

## 5. Sync Queue Mechanism

### 5.1 Sync Manager Architecture

```javascript
class SyncManager {
  constructor() {
    this.db = null;
    this.isOnline = navigator.onLine;
    this.isSyncing = false;
    this.syncInterval = null;
    this.sessionId = this.generateSessionId();
    
    this.init();
  }
  
  async init() {
    // Initialize IndexedDB
    this.db = await this.openDatabase();
    
    // Set up online/offline listeners
    window.addEventListener('online', () => this.handleOnline());
    window.addEventListener('offline', () => this.handleOffline());
    
    // Start periodic sync if online
    if (this.isOnline) {
      this.startPeriodicSync();
    }
  }
  
  async handleOnline() {
    this.isOnline = true;
    this.updateConnectionStatus();
    await this.syncAll();
    this.startPeriodicSync();
  }
  
  handleOffline() {
    this.isOnline = false;
    this.updateConnectionStatus();
    this.stopPeriodicSync();
  }
  
  startPeriodicSync() {
    // Sync every 5 minutes when online
    this.syncInterval = setInterval(() => {
      if (this.isOnline && !this.isSyncing) {
        this.syncAll();
      }
    }, 5 * 60 * 1000);
  }
  
  stopPeriodicSync() {
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
      this.syncInterval = null;
    }
  }
}
```

### 5.2 Queue Operations

```javascript
class SyncManager {
  // ... previous code ...
  
  async addToQueue(operation) {
    const queueItem = {
      id: this.generateUUID(),
      type: operation.type,
      priority: operation.priority || 5,
      payload: operation.payload,
      endpoint: operation.endpoint,
      method: operation.method || 'POST',
      status: 'pending',
      retry_count: 0,
      max_retries: 3,
      created_at: Date.now(),
      lock_token: this.generateLockToken()
    };
    
    await this.db.add('syncQueue', queueItem);
    
    // Attempt sync immediately if online
    if (this.isOnline) {
      this.syncQueue();
    }
    
    return queueItem.id;
  }
  
  async syncQueue() {
    if (this.isSyncing || !this.isOnline) return;
    
    this.isSyncing = true;
    
    try {
      // Get pending items sorted by priority
      const items = await this.db.getAllFromIndex(
        'syncQueue',
        'status',
        'pending'
      );
      
      const sorted = items.sort((a, b) => a.priority - b.priority);
      
      for (const item of sorted) {
        await this.processSyncItem(item);
      }
    } finally {
      this.isSyncing = false;
    }
  }
  
  async processSyncItem(item) {
    try {
      // Update status to processing
      await this.db.put('syncQueue', {
        ...item,
        status: 'processing'
      });
      
      // Make API call with lock token
      const response = await fetch(item.endpoint, {
        method: item.method,
        headers: {
          'Content-Type': 'application/json',
          'X-Lock-Token': item.lock_token,
          'X-Session-Id': this.sessionId
        },
        body: JSON.stringify(item.payload)
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Mark as completed
        await this.db.put('syncQueue', {
          ...item,
          status: 'completed',
          processed_at: Date.now()
        });
        
        // Update local data if needed
        await this.updateLocalData(item.type, result.data);
        
        // Remove from queue after 24 hours
        setTimeout(() => {
          this.db.delete('syncQueue', item.id);
        }, 24 * 60 * 60 * 1000);
        
      } else if (result.error === 'CONFLICT') {
        // Handle conflict
        await this.handleConflict(item, result);
      } else {
        throw new Error(result.message);
      }
      
    } catch (error) {
      // Handle retry logic
      await this.handleSyncError(item, error);
    }
  }
  
  async handleSyncError(item, error) {
    if (item.retry_count < item.max_retries) {
      // Retry with exponential backoff
      const backoff = Math.pow(2, item.retry_count) * 1000;
      
      setTimeout(async () => {
        await this.db.put('syncQueue', {
          ...item,
          status: 'pending',
          retry_count: item.retry_count + 1,
          error: error.message
        });
        
        this.syncQueue();
      }, backoff);
    } else {
      // Max retries reached, mark as failed
      await this.db.put('syncQueue', {
        ...item,
        status: 'failed',
        error: error.message,
        processed_at: Date.now()
      });
      
      // Notify user
      this.showNotification('Sync Failed', {
        body: `Failed to sync ${item.type}: ${error.message}`,
        tag: 'sync-error'
      });
    }
  }
}
```

### 5.3 Sync Priorities

```javascript
const SYNC_PRIORITIES = {
  ORDER_CHECKOUT: 1,      // Highest priority
  STOCK_UPDATE: 2,
  ORDER_REFUND: 3,
  PRODUCT_EDIT: 4,
  SETTINGS_UPDATE: 5,
  DATA_REFRESH: 10        // Lowest priority
};
```

---

## 6. Conflict Prevention Strategy

### 6.1 Optimistic Locking

Each record includes a `sync_version` field that increments on each update:

```javascript
// Client-side update
async function updateProduct(productId, changes) {
  const product = await db.get('products', productId);
  
  const updatePayload = {
    id: productId,
    changes: changes,
    sync_version: product.sync_version,
    lock_token: syncManager.generateLockToken()
  };
  
  // Add to sync queue
  await syncManager.addToQueue({
    type: 'product_update',
    priority: SYNC_PRIORITIES.PRODUCT_EDIT,
    endpoint: '/jpos/api/products.php',
    method: 'PUT',
    payload: updatePayload
  });
}
```

### 6.2 Server-Side Validation

```php
// api/products.php (modified)
public function update_product($data) {
    global $wpdb;
    
    $product_id = $data['id'];
    $client_version = $data['sync_version'];
    $lock_token = $data['lock_token'];
    
    // Get current version from database
    $current_version = get_post_meta($product_id, '_sync_version', true);
    
    // Check for version conflict
    if ($current_version && $client_version < $current_version) {
        return [
            'success' => false,
            'error' => 'CONFLICT',
            'message' => 'Product was modified by another session',
            'current_data' => $this->get_product($product_id),
            'current_version' => $current_version
        ];
    }
    
    // Acquire lock (prevent concurrent modifications)
    $lock_key = "product_lock_{$product_id}";
    $acquired = $wpdb->query($wpdb->prepare(
        "INSERT INTO wp_jpos_locks (lock_key, lock_token, expires_at) 
         VALUES (%s, %s, DATE_ADD(NOW(), INTERVAL 30 SECOND))
         ON DUPLICATE KEY UPDATE 
         lock_token = VALUES(lock_token),
         expires_at = VALUES(expires_at)",
        $lock_key,
        $lock_token
    ));
    
    if (!$acquired) {
        return [
            'success' => false,
            'error' => 'LOCKED',
            'message' => 'Product is being modified by another session'
        ];
    }
    
    // Perform update
    $new_version = $current_version + 1;
    update_post_meta($product_id, '_sync_version', $new_version);
    
    // Apply changes...
    
    // Release lock
    $wpdb->delete('wp_jpos_locks', ['lock_key' => $lock_key]);
    
    return [
        'success' => true,
        'data' => $this->get_product($product_id),
        'sync_version' => $new_version
    ];
}
```

### 6.3 Session Management

```javascript
class SyncManager {
  // ... previous code ...
  
  generateSessionId() {
    return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
  
  generateLockToken() {
    return `lock_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
  
  async handleConflict(item, result) {
    // Server wins - update local data with server version
    if (result.current_data) {
      await this.db.put('products', {
        ...result.current_data,
        sync_version: result.current_version,
        updated_at: Date.now()
      });
    }
    
    // Mark sync item as completed (resolved)
    await this.db.put('syncQueue', {
      ...item,
      status: 'completed',
      error: 'Conflict resolved - server version applied',
      processed_at: Date.now()
    });
    
    // Refresh UI to show server data
    if (typeof window.refreshAllData === 'function') {
      window.refreshAllData();
    }
    
    // Notify user
    this.showNotification('Data Updated', {
      body: 'Your changes were overridden by a newer version from the server',
      tag: 'conflict-resolved'
    });
  }
}
```

---

## 7. Integration with Existing Code

### 7.1 Integration with [`appState`](../assets/js/main.js:8)

```javascript
// Extend appState with offline capabilities
const appState = {
  // ... existing state ...
  
  // NEW: Offline sync state
  offline: {
    isOnline: navigator.onLine,
    lastSyncTime: null,
    syncInProgress: false,
    queueCount: 0,
    errorCount: 0
  },
  
  // NEW: Session management
  session: {
    id: null,
    startedAt: null,
    lockTokens: []
  }
};

// NEW: Add offline state management utilities
function updateOfflineState(updates) {
  Object.assign(appState.offline, updates);
  updateConnectionIndicator();
}

function updateConnectionIndicator() {
  const indicator = document.getElementById('connection-status-indicator');
  if (!indicator) return;
  
  if (appState.offline.isOnline) {
    indicator.classList.remove('bg-red-500');
    indicator.classList.add('bg-green-500');
    indicator.title = 'Online';
    
    if (appState.offline.queueCount > 0) {
      indicator.title = `Online - ${appState.offline.queueCount} items syncing`;
      indicator.classList.add('animate-pulse');
    } else {
      indicator.classList.remove('animate-pulse');
    }
  } else {
    indicator.classList.remove('bg-green-500');
    indicator.classList.add('bg-red-500');
    indicator.title = 'Offline';
    
    if (appState.offline.queueCount > 0) {
      indicator.title = `Offline - ${appState.offline.queueCount} items pending`;
    }
  }
}
```

### 7.2 Integration with Product Loading

Modify [`refreshAllData()`](../assets/js/main.js:318) to use offline data:

```javascript
async function refreshAllData() {
  showSkeletonLoader();
  
  try {
    // Try to fetch from server first
    if (appState.offline.isOnline) {
      const response = await fetch('/jpos/api/products.php');
      if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
      const result = await response.json();
      
      if (result.success) {
        // Store in IndexedDB
        await offlineDataManager.bulkUpdateProducts(result.data.products);
        
        // Update appState
        appState.products.all = result.data.products || [];
        
        // Update filters
        if (document.getElementById('category-filter').options.length <= 1) {
          buildFilterUI(result.data.categories || [], result.data.tags || []);
        }
        
        renderProducts();
        return;
      }
    }
    
    // Fallback to offline data
    const offlineProducts = await offlineDataManager.getAllProducts();
    if (offlineProducts && offlineProducts.length > 0) {
      appState.products.all = offlineProducts;
      renderProducts();
      showToast('Loaded from offline storage');
    } else {
      throw new Error('No offline data available');
    }
    
  } catch (error) {
    console.error('Error loading products:', error);
    
    // Try offline data as final fallback
    try {
      const offlineProducts = await offlineDataManager.getAllProducts();
      if (offlineProducts && offlineProducts.length > 0) {
        appState.products.all = offlineProducts;
        renderProducts();
        showToast('Working offline', 'warning');
      } else {
        throw error;
      }
    } catch (fallbackError) {
      document.getElementById('product-list').innerHTML = 
        `<p class="col-span-full text-center text-red-400">
          Error: Could not load product data. ${error.message}
        </p>`;
    }
  }
}
```

### 7.3 Integration with Checkout

Modify [`processTransaction()`](../assets/js/main.js:3357) to queue orders offline:

```javascript
async function processTransaction() {
  if (appState.cart.items.length === 0 || !appState.drawer.isOpen) return;
  
  // Open split payment modal (existing behavior)
  openSplitPaymentModal();
}

// Modify the actual checkout function
async function performCheckout(splits) {
  const checkoutBtn = document.getElementById('checkout-btn');
  checkoutBtn.disabled = true;
  checkoutBtn.textContent = 'Processing...';
  
  try {
    const payload = {
      cart_items: appState.cart.items,
      payment_method: splits[0].method,
      fee_discount: appState.feeDiscount.type ? appState.feeDiscount : null,
      split_payments: splits.length > 1 ? splits : null,
      nonce: appState.nonces.checkout,
      session_id: syncManager.sessionId,
      lock_token: syncManager.generateLockToken()
    };
    
    if (appState.offline.isOnline) {
      // Try online checkout
      const response = await fetch('/jpos/api/checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      
      const result = await response.json();
      
      if (result.success) {
        clearCart(true);
        await refreshAllData();
        showReceipt(result.data.receipt_data);
      } else {
        throw new Error(result.message);
      }
    } else {
      // Queue for offline sync
      const orderId = await offlineDataManager.createOfflineOrder(payload);
      
      await syncManager.addToQueue({
        type: 'order',
        priority: SYNC_PRIORITIES.ORDER_CHECKOUT,
        endpoint: '/jpos/api/checkout.php',
        method: 'POST',
        payload: payload
      });
      
      clearCart(true);
      showToast('Order queued for sync when online');
      
      // Show offline receipt
      showOfflineReceipt(orderId, payload);
    }
    
  } catch (error) {
    alert(`Checkout failed: ${error.message}`);
  } finally {
    checkoutBtn.disabled = !appState.drawer.isOpen;
    checkoutBtn.textContent = 'Checkout';
  }
}
```

---

## 8. API Modifications

### 8.1 New Database Tables

Create these tables in WordPress database for conflict prevention:

```sql
-- Locks table for preventing concurrent modifications
CREATE TABLE IF NOT EXISTS wp_jpos_locks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lock_key VARCHAR(255) NOT NULL UNIQUE,
  lock_token VARCHAR(255) NOT NULL,
  session_id VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  INDEX idx_lock_key (lock_key),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sync version to products
ALTER TABLE wp_postmeta
ADD INDEX idx_meta_key_sync (_sync_version)
WHERE meta_key = '_sync_version';

-- Sync log for debugging
CREATE TABLE IF NOT EXISTS wp_jpos_sync_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(255),
  operation_type VARCHAR(50),
  entity_type VARCHAR(50),
  entity_id BIGINT,
  status VARCHAR(20),
  error_message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_session (session_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8.2 Modified API Endpoints

#### products.php

Add sync version support:

```php
// Add to response
$product_data['sync_version'] = get_post_meta($product_id, '_sync_version', true) ?: 1;
$product_data['updated_at'] = strtotime($post->post_modified_gmt);

// New endpoint for sync check
if ($_GET['action'] === 'check_sync') {
    $last_sync = $_GET['last_sync'] ?? 0;
    $updated_products = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, pm.meta_value as sync_version
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sync_version'
        WHERE p.post_type IN ('product', 'product_variation')
        AND UNIX_TIMESTAMP(p.post_modified_gmt) > %d
    ", $last_sync));
    
    wp_send_json_success([
        'has_updates' => count($updated_products) > 0,
        'updated_ids' => array_column($updated_products, 'ID'),
        'server_time' => time()
    ]);
}
```

#### checkout.php

Add offline order support with conflict detection:

```php
// Add after existing validation
$session_id = $data['session_id'] ?? null;
$lock_token = $data['lock_token'] ?? null;

// Check for duplicate order (by lock_token)
if ($lock_token) {
    $existing_order = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_jpos_lock_token'
         AND meta_value = %s",
        $lock_token
    ));
    
    if ($existing_order) {
        // Order already processed
        $order = wc_get_order($existing_order);
        wp_send_json_success([
            'message' => 'Order already processed',
            'order_id' => $existing_order,
            'receipt_data' => generate_receipt_data($order)
        ]);
        exit;
    }
}

// Continue with order creation...

// After order creation, store session metadata
$order->add_meta_data('_jpos_session_id', $session_id, true);
$order->add_meta_data('_jpos_lock_token', $lock_token, true);
$order->add_meta_data('_jpos_sync_time', time(), true);
```

#### New: sync.php

Create new endpoint for batch sync operations:

```php
<?php
// api/sync.php - Batch sync endpoint

require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'check_updates':
        check_for_updates();
        break;
    
    case 'batch_sync':
        batch_sync_operations();
        break;
    
    case 'resolve_conflict':
        resolve_conflict();
        break;
    
    default:
        wp_send_json_error(['message' => 'Invalid action'], 400);
}

function check_for_updates() {
    global $wpdb;
    
    $last_sync = $_GET['last_sync'] ?? 0;
    $entity_types = $_GET['types'] ?? ['products', 'orders', 'settings'];
    
    $updates = [];
    
    // Check products
    if (in_array('products', $entity_types)) {
        $updated_products = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type IN ('product', 'product_variation')
            AND UNIX_TIMESTAMP(post_modified_gmt) > %d
        ", $last_sync));
        
        $updates['products'] = [
            'has_updates' => $updated_products > 0,
            'count' => $updated_products
        ];
    }
    
    // Check orders
    if (in_array('orders', $entity_types)) {
        $updated_orders = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND UNIX_TIMESTAMP(post_modified_gmt) > %d
        ", $last_sync));
        
        $updates['orders'] = [
            'has_updates' => $updated_orders > 0,
            'count' => $updated_orders
        ];
    }
    
    wp_send_json_success([
        'updates' => $updates,
        'server_time' => time()
    ]);
}

function batch_sync_operations() {
    $operations = json_decode(file_get_contents('php://input'), true)['operations'] ?? [];
    $results = [];
    
    foreach ($operations as $op) {
        try {
            $result = process_sync_operation($op);
            $results[] = [
                'id' => $op['id'],
                'status' => 'success',
                'result' => $result
            ];
        } catch (Exception $e) {
            $results[] = [
                'id' => $op['id'],
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    wp_send_json_success(['results' => $results]);
}

function process_sync_operation($operation) {
    // Process based on operation type
    switch ($operation['type']) {
        case 'order':
            return process_order_sync($operation);
        case 'product_update':
            return process_product_update($operation);
        case 'stock_update':
            return process_stock_update($operation);
        default:
            throw new Exception('Unknown operation type');
    }
}
```

### 8.3 New Headers for Conflict Detection

All API endpoints should accept these headers:

```php
// Extract conflict detection headers
$session_id = $_SERVER['HTTP_X_SESSION_ID'] ?? null;
$lock_token = $_SERVER['HTTP_X_LOCK_TOKEN'] ?? null;
$client_version = $_SERVER['HTTP_X_SYNC_VERSION'] ?? null;
```

---

## 9. File Structure & Organization

```
/home/u479157563/domains/jonesytt.com/public_html/wp-pos/
│
├── index.php                          # Main entry point (existing)
├── sw.js                              # NEW: Service Worker
├── manifest.json                      # NEW: PWA Manifest
│
├── assets/
│   └── js/
│       ├── main.js                    # Existing - modify for offline
│       ├── offline/                   # NEW: Offline functionality
│       │   ├── offline-data-manager.js      # IndexedDB operations
│       │   ├── sync-manager.js              # Sync queue management
│       │   ├── connection-monitor.js        # Online/offline detection
│       │   └── conflict-resolver.js         # Conflict resolution
│       └── modules/
│           ├── state.js               # Existing - extend with offline state
│           └── routing.js             # Existing
│
├── api/
│   ├── products.php                   # Modify for sync support
│   ├── checkout.php                   # Modify for offline orders
│   ├── orders.php                     # Modify for sync support
│   ├── sync.php                       # NEW: Batch sync endpoint
│   └── locks.php                      # NEW: Lock management
│
├── docs/
│   ├── OFFLINE_SYNC_ARCHITECTURE.md   # This document
│   ├── OFFLINE_IMPLEMENTATION.md      # NEW: Implementation guide
│   └── OFFLINE_API_SPEC.md            # NEW: API specifications
│
└── tests/
    └── offline/                       # NEW: Offline tests
        ├── test-indexeddb.js
        ├── test-sync-manager.js
        └── test-service-worker.js
```

---

## 10. Data Flow Diagrams

### 10.1 Product Load Flow

```
┌─────────────┐
│   UI Load   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐     Yes    ┌──────────────┐
│  Is Online?     │───────────►│ Fetch from   │
└─────────────────┘            │ Server       │
       │ No                     └──────┬───────┘
       │                               │
       ▼                               ▼
┌─────────────────┐            ┌──────────────┐
│ Load from       │            │ Store in     │
│ IndexedDB       │◄───────────│ IndexedDB    │
└─────────┬───────┘            └──────────────┘
          │                            │
          │                            │
          ▼                            ▼
   ┌─────────────────┐          ┌─────────────┐
   │ Update appState │          │ Update      │
   │ & Render UI     │          │ appState &  │
   └─────────────────┘          │ Render UI   │
                                └─────────────┘
```

### 10.2 Order Checkout Flow (Online)

```
┌─────────────┐
│ User clicks │
│  Checkout   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Generate Lock   │
│ Token & Session │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐     Success   ┌──────────────┐
│ POST to         │──────────────►│ Clear Cart   │
│ checkout.php    │                └──────┬───────┘
└─────────────────┘                       │
       │ Error                             ▼
       │                            ┌──────────────┐
       ▼                            │ Show Receipt │
┌─────────────────┐                └──────────────┘
│ Show Error      │
│ Message         │
└─────────────────┘
```

### 10.3 Order Checkout Flow (Offline)

```
┌─────────────┐
│ User clicks │
│  Checkout   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Detect Offline  │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Create Local    │
│ Order Record    │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Add to Sync     │
│ Queue (Priority │
│ = 1)            │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Clear Cart      │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Show Offline    │
│ Receipt with    │
│ "Pending Sync"  │
└─────────────────┘
       │
       │ (When connection restored)
       ▼
┌─────────────────┐
│ Sync Manager    │
│ processes queue │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐     Success   ┌──────────────┐
│ POST to         │──────────────►│ Update Local │
│ checkout.php    │                │ with Server  │
│ with Lock Token │                │ Order ID     │
└─────────────────┘                └──────┬───────┘
       │ Duplicate                         │
       │ (Lock Token                       ▼
       │  exists)               ┌──────────────────┐
       │                        │ Mark as Synced   │
       ▼                        │ & Notify User    │
┌─────────────────┐            └──────────────────┘
│ Mark as Synced  │
│ (Already        │
│  processed)     │
└─────────────────┘
```

### 10.4 Background Sync Flow

```
┌────────────────┐
│ Connection     │
│ Restored       │
└───────┬────────┘
        │
        ▼
┌────────────────┐
│ Sync Manager   │
│ Triggered      │
└───────┬────────┘
        │
        ▼
┌────────────────────┐
│ Get Pending Items  │
│ from Sync Queue    │
│ (sorted by         │
│  priority)         │
└───────┬────────────┘
        │
        ▼
┌────────────────────┐
│ For each item:     │
│ 1. Check lock      │
│ 2. Send request    │
│ 3. Handle response │
└───────┬────────────┘
        │
   ┌────┴─────┐
   │          │
Success    Conflict/Error
   │          │
   ▼          ▼
┌──────┐  ┌────────┐
│ Mark │  │ Retry  │
│ Done │  │ or     │
│      │  │ Resolve│
└──────┘  └────────┘
```

---

## 11. Implementation Phases

### Phase 1: Foundation (Week 1)
- [ ] Create IndexedDB schema and OfflineDataManager class
- [ ] Implement basic Service Worker with static caching
- [ ] Add online/offline detection and UI indicators
- [ ] Test basic offline functionality

**Deliverables:**
- [`offline-data-manager.js`](../assets/js/offline/offline-data-manager.js)
- [`connection-monitor.js`](../assets/js/offline/connection-monitor.js)
- [`sw.js`](../sw.js)
- Basic UI indicators in header

### Phase 2: Product Catalog Offline (Week 2)
- [ ] Implement product caching in IndexedDB
- [ ] Modify [`refreshAllData()`](../assets/js/main.js:318) to use offline storage
- [ ] Implement cache-first strategy for products
- [ ] Add background product sync

**Deliverables:**
- Products work fully offline
- Automatic sync when online
- Visual feedback during sync

### Phase 3: Offline Orders & Sync Queue (Week 3)
- [ ] Implement SyncManager class with queue
- [ ] Create offline order creation
- [ ] Implement sync queue processing
- [ ] Add retry logic with exponential backoff

**Deliverables:**
- [`sync-manager.js`](../assets/js/offline/sync-manager.js)
- Orders can be created offline
- Automatic sync when connection restored

### Phase 4: Conflict Prevention (Week 4)
- [ ] Implement optimistic locking system
- [ ] Add lock token generation
- [ ] Modify API endpoints for conflict detection
- [ ] Create conflict resolver

**Deliverables:**
- [`conflict-resolver.js`](../assets/js/offline/conflict-resolver.js)
- [`locks.php`](../api/locks.php)
- Modified API endpoints
- Conflict resolution UI

### Phase 5: Advanced Features (Week 5)
- [ ] Implement periodic background sync
- [ ] Add sync status dashboard
- [ ] Create PWA manifest
- [ ] Add push notifications for sync events

**Deliverables:**
- [`manifest.json`](../manifest.json)
- Sync dashboard page
- Push notification system

### Phase 6: Testing & Optimization (Week 6)
- [ ] Write comprehensive tests
- [ ] Performance optimization
- [ ] Browser compatibility testing
- [ ] Documentation finalization

**Deliverables:**
- Test suite
- Performance benchmarks
- Complete documentation

---

## 12. Performance Considerations

### 12.1 IndexedDB Optimization

**Batch Operations:**
```javascript
// Instead of multiple single writes
for (const product of products) {
  await db.put('products', product); // Slow
}

// Use transactions for bulk operations
const tx = db.transaction('products', 'readwrite');
const store = tx.objectStore('products');
for (const product of products) {
  store.put(product); // Fast
}
await tx.complete;
```

**Indexed Queries:**
```javascript
// Always use indexes for filtering
const instockProducts = await db.getAllFromIndex(
  'products',
  'stock_status',
  'instock'
);
```

**Cursor Pagination:**
```javascript
// For large datasets, use cursors instead of getAll()
const results = [];
let cursor = await db.transaction('products')
  .objectStore('products')
  .openCursor();

while (cursor && results.length < 50) {
  results.push(cursor.value);
  cursor = await cursor.continue();
}
```

### 12.2 Service Worker Optimization

**Selective Caching:**
```javascript
// Only cache essential API responses
const CACHEABLE_APIS = [
  '/jpos/api/products.php',
  '/jpos/api/settings.php'
];

// Skip caching for dynamic data
const NO_CACHE_APIS = [
  '/jpos/api/checkout.php',
  '/jpos/api/drawer.php'
];
```

**Cache Invalidation:**
```javascript
// Invalidate cache after specific time
const CACHE_MAX_AGE = {
  'products': 60 * 60 * 1000,      // 1 hour
  'images': 24 * 60 * 60 * 1000,   // 24 hours
  'static': 7 * 24 * 60 * 60 * 1000 // 7 days
};
```

### 12.3 Memory Management

**Limit IndexedDB Size:**
```javascript
const MAX_PRODUCTS = 10000;
const MAX_ORDERS = 1000;
const MAX_SYNC_QUEUE = 500;

// Implement cleanup for old data
async function cleanupOldData() {
  // Remove synced orders older than 30 days
  const cutoff = Date.now() - (30 * 24 * 60 * 60 * 1000);
  const tx = db.transaction('orders', 'readwrite');
  const store = tx.objectStore('orders');
  const index = store.index('synced_at');
  
  for await (const cursor of index.iterate()) {
    if (cursor.value.synced_at < cutoff) {
      cursor.delete();
    }
  }
}
```

### 12.4 Network Efficiency

**Request Debouncing:**
```javascript
let syncTimeout;
function scheduleSyncunction scheduleSyncSync() {
  clearTimeout(syncTimeout);
  syncTimeout = setTimeout(() => {
    syncManager.syncAll();
  }, 2000); // Wait 2s after last change
}
```

**Batch API Calls:**
```javascript
// Instead of syncing items one by one
// Batch multiple operations into single request
const batchPayload = {
  operations: queueItems.map(item => ({
    type: item.type,
    payload: item.payload
  }))
};

await fetch('/jpos/api/sync.php?action=batch_sync', {
  method: 'POST',
  body: JSON.stringify(batchPayload)
});
```

---

## Summary

This architecture provides a comprehensive offline-first solution for the WordPress POS system with:

✅ **Full offline capability** for all critical operations
✅ **Aggressive sync strategy** with background sync + periodic sync
✅ **Conflict prevention** through optimistic locking and session management
✅ **Progressive Web App** features via Service Workers
✅ **Seamless integration** with existing codebase
✅ **Performance optimization** throughout the stack

### Next Steps

1. Review and approve this architecture document
2. Switch to **Code mode** to begin implementation
3. Follow the 6-phase implementation plan
4. Test thoroughly at each phase

### Questions or Modifications?

If you need any clarifications or modifications to this architecture, please let me know before we proceed to implementation.

---

**Document Version:** 1.0
**Last Updated:** 2025-10-04
**Status:** Ready for Review & Implementation