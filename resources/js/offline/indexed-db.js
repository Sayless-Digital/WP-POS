/**
 * IndexedDB Manager for WP-POS
 * Handles local storage for offline functionality
 */

class IndexedDBManager {
    constructor() {
        this.dbName = 'POSDatabase';
        this.version = 1;
        this.db = null;
    }

    /**
     * Initialize the database
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => {
                console.error('[IndexedDB] Error opening database:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('[IndexedDB] Database opened successfully');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                console.log('[IndexedDB] Upgrading database...');

                // Products store
                if (!db.objectStoreNames.contains('products')) {
                    const productsStore = db.createObjectStore('products', { keyPath: 'id' });
                    productsStore.createIndex('sku', 'sku', { unique: true });
                    productsStore.createIndex('barcode', 'barcode', { unique: false });
                    productsStore.createIndex('name', 'name', { unique: false });
                    console.log('[IndexedDB] Created products store');
                }

                // Pending orders store
                if (!db.objectStoreNames.contains('pendingOrders')) {
                    const ordersStore = db.createObjectStore('pendingOrders', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    ordersStore.createIndex('timestamp', 'timestamp', { unique: false });
                    ordersStore.createIndex('status', 'status', { unique: false });
                    ordersStore.createIndex('orderNumber', 'orderNumber', { unique: true });
                    console.log('[IndexedDB] Created pendingOrders store');
                }

                // Sync queue store
                if (!db.objectStoreNames.contains('syncQueue')) {
                    const queueStore = db.createObjectStore('syncQueue', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    queueStore.createIndex('status', 'status', { unique: false });
                    queueStore.createIndex('type', 'type', { unique: false });
                    queueStore.createIndex('timestamp', 'timestamp', { unique: false });
                    console.log('[IndexedDB] Created syncQueue store');
                }

                // Customers cache store
                if (!db.objectStoreNames.contains('customers')) {
                    const customersStore = db.createObjectStore('customers', { keyPath: 'id' });
                    customersStore.createIndex('email', 'email', { unique: false });
                    customersStore.createIndex('phone', 'phone', { unique: false });
                    console.log('[IndexedDB] Created customers store');
                }

                // Settings cache store
                if (!db.objectStoreNames.contains('settings')) {
                    db.createObjectStore('settings', { keyPath: 'key' });
                    console.log('[IndexedDB] Created settings store');
                }
            };
        });
    }

    // ==================== PRODUCTS ====================

    /**
     * Add or update a product
     */
    async addProduct(product) {
        const transaction = this.db.transaction(['products'], 'readwrite');
        const store = transaction.objectStore('products');

        return new Promise((resolve, reject) => {
            const request = store.put(product);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get a product by ID
     */
    async getProduct(id) {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');

        return new Promise((resolve, reject) => {
            const request = store.get(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get a product by barcode
     */
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

    /**
     * Get a product by SKU
     */
    async getProductBySKU(sku) {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');
        const index = store.index('sku');

        return new Promise((resolve, reject) => {
            const request = index.get(sku);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get all products
     */
    async getAllProducts() {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Search products by name
     */
    async searchProducts(query) {
        const products = await this.getAllProducts();
        const lowerQuery = query.toLowerCase();

        return products.filter(product =>
            product.name.toLowerCase().includes(lowerQuery) ||
            product.sku.toLowerCase().includes(lowerQuery)
        );
    }

    /**
     * Bulk add products
     */
    async bulkAddProducts(products) {
        const transaction = this.db.transaction(['products'], 'readwrite');
        const store = transaction.objectStore('products');

        return new Promise((resolve, reject) => {
            let completed = 0;
            const total = products.length;

            products.forEach(product => {
                const request = store.put(product);

                request.onsuccess = () => {
                    completed++;
                    if (completed === total) {
                        resolve(completed);
                    }
                };

                request.onerror = () => {
                    console.error('[IndexedDB] Error adding product:', request.error);
                };
            });

            if (total === 0) {
                resolve(0);
            }
        });
    }

    /**
     * Clear all products
     */
    async clearProducts() {
        const transaction = this.db.transaction(['products'], 'readwrite');
        const store = transaction.objectStore('products');

        return new Promise((resolve, reject) => {
            const request = store.clear();
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // ==================== PENDING ORDERS ====================

    /**
     * Add a pending order
     */
    async addPendingOrder(order) {
        const transaction = this.db.transaction(['pendingOrders'], 'readwrite');
        const store = transaction.objectStore('pendingOrders');

        order.timestamp = Date.now();
        order.status = order.status || 'pending';
        order.attempts = 0;

        return new Promise((resolve, reject) => {
            const request = store.add(order);
            request.onsuccess = () => {
                console.log('[IndexedDB] Pending order added:', request.result);
                resolve(request.result);
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get all pending orders
     */
    async getPendingOrders() {
        const transaction = this.db.transaction(['pendingOrders'], 'readonly');
        const store = transaction.objectStore('pendingOrders');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get a pending order by ID
     */
    async getPendingOrder(id) {
        const transaction = this.db.transaction(['pendingOrders'], 'readonly');
        const store = transaction.objectStore('pendingOrders');

        return new Promise((resolve, reject) => {
            const request = store.get(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Update a pending order
     */
    async updatePendingOrder(id, updates) {
        const transaction = this.db.transaction(['pendingOrders'], 'readwrite');
        const store = transaction.objectStore('pendingOrders');

        return new Promise((resolve, reject) => {
            const getRequest = store.get(id);

            getRequest.onsuccess = () => {
                const order = getRequest.result;
                if (!order) {
                    reject(new Error('Order not found'));
                    return;
                }

                Object.assign(order, updates);

                const putRequest = store.put(order);
                putRequest.onsuccess = () => resolve(putRequest.result);
                putRequest.onerror = () => reject(putRequest.error);
            };

            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Remove a pending order
     */
    async removePendingOrder(id) {
        const transaction = this.db.transaction(['pendingOrders'], 'readwrite');
        const store = transaction.objectStore('pendingOrders');

        return new Promise((resolve, reject) => {
            const request = store.delete(id);
            request.onsuccess = () => {
                console.log('[IndexedDB] Pending order removed:', id);
                resolve();
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get pending orders count
     */
    async getPendingOrdersCount() {
        const transaction = this.db.transaction(['pendingOrders'], 'readonly');
        const store = transaction.objectStore('pendingOrders');

        return new Promise((resolve, reject) => {
            const request = store.count();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // ==================== SYNC QUEUE ====================

    /**
     * Add item to sync queue
     */
    async addToSyncQueue(item) {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');

        item.timestamp = Date.now();
        item.status = 'pending';
        item.attempts = 0;

        return new Promise((resolve, reject) => {
            const request = store.add(item);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get sync queue items
     */
    async getSyncQueue(status = 'pending') {
        const transaction = this.db.transaction(['syncQueue'], 'readonly');
        const store = transaction.objectStore('syncQueue');
        const index = store.index('status');

        return new Promise((resolve, reject) => {
            const request = index.getAll(status);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Update sync queue item
     */
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

    /**
     * Remove sync queue item
     */
    async removeSyncQueueItem(id) {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');

        return new Promise((resolve, reject) => {
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Clear sync queue
     */
    async clearSyncQueue() {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');

        return new Promise((resolve, reject) => {
            const request = store.clear();
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // ==================== CUSTOMERS ====================

    /**
     * Add or update a customer
     */
    async addCustomer(customer) {
        const transaction = this.db.transaction(['customers'], 'readwrite');
        const store = transaction.objectStore('customers');

        return new Promise((resolve, reject) => {
            const request = store.put(customer);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get a customer by ID
     */
    async getCustomer(id) {
        const transaction = this.db.transaction(['customers'], 'readonly');
        const store = transaction.objectStore('customers');

        return new Promise((resolve, reject) => {
            const request = store.get(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get all customers
     */
    async getAllCustomers() {
        const transaction = this.db.transaction(['customers'], 'readonly');
        const store = transaction.objectStore('customers');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Search customers
     */
    async searchCustomers(query) {
        const customers = await this.getAllCustomers();
        const lowerQuery = query.toLowerCase();

        return customers.filter(customer =>
            customer.name?.toLowerCase().includes(lowerQuery) ||
            customer.email?.toLowerCase().includes(lowerQuery) ||
            customer.phone?.includes(query)
        );
    }

    // ==================== SETTINGS ====================

    /**
     * Save a setting
     */
    async saveSetting(key, value) {
        const transaction = this.db.transaction(['settings'], 'readwrite');
        const store = transaction.objectStore('settings');

        return new Promise((resolve, reject) => {
            const request = store.put({ key, value, updated_at: Date.now() });
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get a setting
     */
    async getSetting(key) {
        const transaction = this.db.transaction(['settings'], 'readonly');
        const store = transaction.objectStore('settings');

        return new Promise((resolve, reject) => {
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result?.value);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get all settings
     */
    async getAllSettings() {
        const transaction = this.db.transaction(['settings'], 'readonly');
        const store = transaction.objectStore('settings');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => {
                const settings = {};
                request.result.forEach(item => {
                    settings[item.key] = item.value;
                });
                resolve(settings);
            };
            request.onerror = () => reject(request.error);
        });
    }

    // ==================== UTILITIES ====================

    /**
     * Get database statistics
     */
    async getStats() {
        const [
            productsCount,
            pendingOrdersCount,
            syncQueueCount,
            customersCount
        ] = await Promise.all([
            this.getCount('products'),
            this.getCount('pendingOrders'),
            this.getCount('syncQueue'),
            this.getCount('customers')
        ]);

        return {
            products: productsCount,
            pendingOrders: pendingOrdersCount,
            syncQueue: syncQueueCount,
            customers: customersCount
        };
    }

    /**
     * Get count of items in a store
     */
    async getCount(storeName) {
        const transaction = this.db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            const request = store.count();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Clear all data
     */
    async clearAll() {
        const stores = ['products', 'pendingOrders', 'syncQueue', 'customers', 'settings'];

        const promises = stores.map(storeName => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);

            return new Promise((resolve, reject) => {
                const request = store.clear();
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        });

        return Promise.all(promises);
    }

    /**
     * Close database connection
     */
    close() {
        if (this.db) {
            this.db.close();
            this.db = null;
            console.log('[IndexedDB] Database closed');
        }
    }
}

// Create and export singleton instance
const dbManager = new IndexedDBManager();

// Initialize on load
if (typeof window !== 'undefined') {
    window.dbManager = dbManager;
    
    // Auto-initialize
    dbManager.init().catch(error => {
        console.error('[IndexedDB] Failed to initialize:', error);
    });
}

export default dbManager;