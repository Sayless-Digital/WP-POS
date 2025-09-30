/**
 * Connection Monitor for WP-POS
 * Monitors network status and handles online/offline transitions
 */

import dbManager from './indexed-db.js';

class ConnectionMonitor {
    constructor() {
        this.isOnline = navigator.onLine;
        this.lastSync = null;
        this.pendingCount = 0;
        this.checkInterval = null;
        this.listeners = [];
    }

    /**
     * Initialize the connection monitor
     */
    init() {
        console.log('[ConnectionMonitor] Initializing...');
        
        // Set up event listeners
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Initial connection check
        this.checkConnection();
        
        // Load pending count
        this.loadPendingCount();
        
        // Start periodic checks (every 30 seconds)
        this.startPeriodicCheck();
        
        // Listen for service worker messages
        this.listenForServiceWorkerMessages();
        
        console.log('[ConnectionMonitor] Initialized');
    }

    /**
     * Start periodic connection checks
     */
    startPeriodicCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        
        this.checkInterval = setInterval(() => {
            this.checkConnection();
        }, 30000); // Every 30 seconds
    }

    /**
     * Stop periodic checks
     */
    stopPeriodicCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }

    /**
     * Check current connection status
     */
    async checkConnection() {
        if (!navigator.onLine) {
            this.updateStatus(false);
            return false;
        }

        try {
            const response = await fetch('/api/ping', {
                method: 'HEAD',
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            });

            const isOnline = response.ok;
            this.updateStatus(isOnline);
            return isOnline;
        } catch (error) {
            console.log('[ConnectionMonitor] Connection check failed:', error.message);
            this.updateStatus(false);
            return false;
        }
    }

    /**
     * Update connection status
     */
    updateStatus(isOnline) {
        const wasOnline = this.isOnline;
        this.isOnline = isOnline;

        if (wasOnline !== isOnline) {
            console.log('[ConnectionMonitor] Status changed:', isOnline ? 'ONLINE' : 'OFFLINE');
            this.notifyListeners('statusChanged', { isOnline });
        }
    }

    /**
     * Handle online event
     */
    async handleOnline() {
        console.log('[ConnectionMonitor] Connection restored');
        this.updateStatus(true);

        // Show notification
        this.showNotification('success', 'Connection restored. Syncing data...');

        // Trigger sync
        await this.syncPendingData();

        // Notify listeners
        this.notifyListeners('online');
    }

    /**
     * Handle offline event
     */
    handleOffline() {
        console.log('[ConnectionMonitor] Connection lost');
        this.updateStatus(false);

        // Show notification
        this.showNotification('warning', 'Working offline. Data will sync when connection is restored.');

        // Notify listeners
        this.notifyListeners('offline');
    }

    /**
     * Sync pending data to server
     */
    async syncPendingData() {
        try {
            console.log('[ConnectionMonitor] Starting sync...');

            // Get pending orders
            const pendingOrders = await dbManager.getPendingOrders();
            console.log(`[ConnectionMonitor] Found ${pendingOrders.length} pending orders`);

            let successCount = 0;
            let failCount = 0;

            // Sync each order
            for (const order of pendingOrders) {
                try {
                    const response = await fetch('/api/orders/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify(order)
                    });

                    if (response.ok) {
                        const result = await response.json();
                        console.log('[ConnectionMonitor] Order synced:', result);

                        // Remove from pending
                        await dbManager.removePendingOrder(order.id);
                        successCount++;

                        // Notify listeners
                        this.notifyListeners('orderSynced', { order, result });
                    } else {
                        console.error('[ConnectionMonitor] Order sync failed:', response.status);
                        
                        // Update attempt count
                        await dbManager.updatePendingOrder(order.id, {
                            attempts: (order.attempts || 0) + 1,
                            lastAttempt: Date.now()
                        });
                        
                        failCount++;
                    }
                } catch (error) {
                    console.error('[ConnectionMonitor] Error syncing order:', error);
                    failCount++;
                }
            }

            // Update last sync time
            this.lastSync = new Date();

            // Refresh product cache
            await this.refreshProductCache();

            // Update pending count
            await this.loadPendingCount();

            // Show result notification
            if (successCount > 0) {
                this.showNotification('success', `Synced ${successCount} order(s) successfully`);
            }

            if (failCount > 0) {
                this.showNotification('error', `Failed to sync ${failCount} order(s)`);
            }

            // Notify listeners
            this.notifyListeners('syncComplete', { successCount, failCount });

            console.log('[ConnectionMonitor] Sync complete:', { successCount, failCount });
        } catch (error) {
            console.error('[ConnectionMonitor] Sync failed:', error);
            this.showNotification('error', 'Sync failed. Will retry later.');
            this.notifyListeners('syncError', { error });
        }
    }

    /**
     * Refresh product cache from server
     */
    async refreshProductCache() {
        try {
            console.log('[ConnectionMonitor] Refreshing product cache...');

            const response = await fetch('/api/products/cache', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const products = await response.json();
                console.log(`[ConnectionMonitor] Received ${products.length} products`);

                // Clear existing products
                await dbManager.clearProducts();

                // Add new products
                await dbManager.bulkAddProducts(products);

                console.log('[ConnectionMonitor] Product cache refreshed');
                this.notifyListeners('cacheRefreshed', { count: products.length });
            }
        } catch (error) {
            console.error('[ConnectionMonitor] Cache refresh failed:', error);
        }
    }

    /**
     * Load pending orders count
     */
    async loadPendingCount() {
        try {
            this.pendingCount = await dbManager.getPendingOrdersCount();
            this.notifyListeners('pendingCountChanged', { count: this.pendingCount });
        } catch (error) {
            console.error('[ConnectionMonitor] Failed to load pending count:', error);
        }
    }

    /**
     * Listen for service worker messages
     */
    listenForServiceWorkerMessages() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                console.log('[ConnectionMonitor] Service worker message:', event.data);

                switch (event.data.type) {
                    case 'order-synced':
                        this.loadPendingCount();
                        this.notifyListeners('orderSynced', event.data);
                        break;
                    case 'inventory-synced':
                        this.notifyListeners('inventorySynced', event.data);
                        break;
                    case 'cache-updated':
                        this.notifyListeners('cacheUpdated', event.data);
                        break;
                }
            });
        }
    }

    /**
     * Show notification to user
     */
    showNotification(type, message) {
        // Dispatch custom event for UI to handle
        window.dispatchEvent(new CustomEvent('pos-notification', {
            detail: { type, message }
        }));

        // Also log to console
        console.log(`[ConnectionMonitor] ${type.toUpperCase()}: ${message}`);
    }

    /**
     * Add event listener
     */
    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (!this.listeners[event]) return;
        
        this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
    }

    /**
     * Notify all listeners of an event
     */
    notifyListeners(event, data = {}) {
        if (!this.listeners[event]) return;

        this.listeners[event].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error('[ConnectionMonitor] Listener error:', error);
            }
        });
    }

    /**
     * Get current status
     */
    getStatus() {
        return {
            isOnline: this.isOnline,
            lastSync: this.lastSync,
            pendingCount: this.pendingCount
        };
    }

    /**
     * Force sync now
     */
    async forceSync() {
        if (!this.isOnline) {
            this.showNotification('warning', 'Cannot sync while offline');
            return false;
        }

        await this.syncPendingData();
        return true;
    }

    /**
     * Cleanup
     */
    destroy() {
        this.stopPeriodicCheck();
        window.removeEventListener('online', this.handleOnline);
        window.removeEventListener('offline', this.handleOffline);
        this.listeners = [];
        console.log('[ConnectionMonitor] Destroyed');
    }
}

// Create and export singleton instance
const connectionMonitor = new ConnectionMonitor();

// Initialize on load
if (typeof window !== 'undefined') {
    window.connectionMonitor = connectionMonitor;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            connectionMonitor.init();
        });
    } else {
        connectionMonitor.init();
    }
}

export default connectionMonitor;