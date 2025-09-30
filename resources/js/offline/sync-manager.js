/**
 * Sync Manager for WP-POS
 * Handles background synchronization of offline data
 */

import dbManager from './indexed-db.js';

class SyncManager {
    constructor() {
        this.isSyncing = false;
        this.syncQueue = [];
        this.maxRetries = 3;
        this.retryDelay = 5000; // 5 seconds
    }

    /**
     * Initialize sync manager
     */
    init() {
        console.log('[SyncManager] Initializing...');

        // Register background sync if supported
        if ('serviceWorker' in navigator && 'sync' in ServiceWorkerRegistration.prototype) {
            this.registerBackgroundSync();
        }

        console.log('[SyncManager] Initialized');
    }

    /**
     * Register background sync
     */
    async registerBackgroundSync() {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            // Register sync for orders
            await registration.sync.register('sync-orders');
            console.log('[SyncManager] Background sync registered for orders');

            // Register sync for inventory
            await registration.sync.register('sync-inventory');
            console.log('[SyncManager] Background sync registered for inventory');
        } catch (error) {
            console.error('[SyncManager] Background sync registration failed:', error);
        }
    }

    /**
     * Queue an order for sync
     */
    async queueOrder(orderData) {
        try {
            console.log('[SyncManager] Queueing order for sync...');

            // Add to IndexedDB pending orders
            const orderId = await dbManager.addPendingOrder(orderData);

            // Add to sync queue
            await dbManager.addToSyncQueue({
                type: 'order',
                action: 'create',
                data: orderData,
                referenceId: orderId
            });

            console.log('[SyncManager] Order queued:', orderId);

            // Try immediate sync if online
            if (navigator.onLine) {
                this.syncOrders();
            }

            return orderId;
        } catch (error) {
            console.error('[SyncManager] Failed to queue order:', error);
            throw error;
        }
    }

    /**
     * Sync pending orders
     */
    async syncOrders() {
        if (this.isSyncing) {
            console.log('[SyncManager] Sync already in progress');
            return;
        }

        if (!navigator.onLine) {
            console.log('[SyncManager] Cannot sync while offline');
            return;
        }

        this.isSyncing = true;

        try {
            console.log('[SyncManager] Starting order sync...');

            const pendingOrders = await dbManager.getPendingOrders();
            console.log(`[SyncManager] Found ${pendingOrders.length} pending orders`);

            const results = {
                success: 0,
                failed: 0,
                errors: []
            };

            for (const order of pendingOrders) {
                try {
                    const result = await this.syncOrder(order);
                    
                    if (result.success) {
                        // Remove from pending
                        await dbManager.removePendingOrder(order.id);
                        results.success++;
                        
                        console.log('[SyncManager] Order synced successfully:', order.orderNumber);
                    } else {
                        // Update retry count
                        const attempts = (order.attempts || 0) + 1;
                        
                        if (attempts >= this.maxRetries) {
                            // Mark as failed after max retries
                            await dbManager.updatePendingOrder(order.id, {
                                status: 'failed',
                                attempts,
                                lastError: result.error,
                                lastAttempt: Date.now()
                            });
                            
                            results.failed++;
                            results.errors.push({
                                order: order.orderNumber,
                                error: result.error
                            });
                        } else {
                            // Update attempt count
                            await dbManager.updatePendingOrder(order.id, {
                                attempts,
                                lastAttempt: Date.now()
                            });
                        }
                        
                        console.error('[SyncManager] Order sync failed:', order.orderNumber, result.error);
                    }
                } catch (error) {
                    console.error('[SyncManager] Error processing order:', error);
                    results.failed++;
                    results.errors.push({
                        order: order.orderNumber,
                        error: error.message
                    });
                }
            }

            console.log('[SyncManager] Order sync complete:', results);
            return results;
        } catch (error) {
            console.error('[SyncManager] Order sync failed:', error);
            throw error;
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Sync a single order
     */
    async syncOrder(order) {
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

            if (!response.ok) {
                const error = await response.text();
                return {
                    success: false,
                    error: `HTTP ${response.status}: ${error}`
                };
            }

            const result = await response.json();
            return {
                success: true,
                data: result
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Queue inventory update for sync
     */
    async queueInventoryUpdate(productId, quantity, reason) {
        try {
            console.log('[SyncManager] Queueing inventory update...');

            await dbManager.addToSyncQueue({
                type: 'inventory',
                action: 'update',
                data: {
                    productId,
                    quantity,
                    reason,
                    timestamp: Date.now()
                }
            });

            console.log('[SyncManager] Inventory update queued');

            // Try immediate sync if online
            if (navigator.onLine) {
                this.syncInventory();
            }
        } catch (error) {
            console.error('[SyncManager] Failed to queue inventory update:', error);
            throw error;
        }
    }

    /**
     * Sync inventory updates
     */
    async syncInventory() {
        if (!navigator.onLine) {
            console.log('[SyncManager] Cannot sync inventory while offline');
            return;
        }

        try {
            console.log('[SyncManager] Syncing inventory...');

            const queueItems = await dbManager.getSyncQueue('pending');
            const inventoryItems = queueItems.filter(item => item.type === 'inventory');

            console.log(`[SyncManager] Found ${inventoryItems.length} inventory updates`);

            for (const item of inventoryItems) {
                try {
                    const response = await fetch('/api/inventory/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify(item.data)
                    });

                    if (response.ok) {
                        await dbManager.removeSyncQueueItem(item.id);
                        console.log('[SyncManager] Inventory update synced');
                    } else {
                        const attempts = (item.attempts || 0) + 1;
                        await dbManager.updateSyncQueueItem(item.id, {
                            attempts,
                            status: attempts >= this.maxRetries ? 'failed' : 'pending'
                        });
                    }
                } catch (error) {
                    console.error('[SyncManager] Error syncing inventory:', error);
                }
            }

            console.log('[SyncManager] Inventory sync complete');
        } catch (error) {
            console.error('[SyncManager] Inventory sync failed:', error);
        }
    }

    /**
     * Sync all pending data
     */
    async syncAll() {
        console.log('[SyncManager] Starting full sync...');

        const results = {
            orders: null,
            inventory: null
        };

        try {
            // Sync orders
            results.orders = await this.syncOrders();

            // Sync inventory
            await this.syncInventory();
            results.inventory = { success: true };

            console.log('[SyncManager] Full sync complete:', results);
            return results;
        } catch (error) {
            console.error('[SyncManager] Full sync failed:', error);
            throw error;
        }
    }

    /**
     * Get sync status
     */
    async getStatus() {
        try {
            const [pendingOrders, syncQueue] = await Promise.all([
                dbManager.getPendingOrders(),
                dbManager.getSyncQueue('pending')
            ]);

            return {
                isSyncing: this.isSyncing,
                pendingOrders: pendingOrders.length,
                syncQueue: syncQueue.length,
                orders: pendingOrders,
                queue: syncQueue
            };
        } catch (error) {
            console.error('[SyncManager] Failed to get status:', error);
            return {
                isSyncing: this.isSyncing,
                pendingOrders: 0,
                syncQueue: 0,
                error: error.message
            };
        }
    }

    /**
     * Clear failed sync items
     */
    async clearFailed() {
        try {
            const failedItems = await dbManager.getSyncQueue('failed');
            
            for (const item of failedItems) {
                await dbManager.removeSyncQueueItem(item.id);
            }

            console.log(`[SyncManager] Cleared ${failedItems.length} failed items`);
            return failedItems.length;
        } catch (error) {
            console.error('[SyncManager] Failed to clear failed items:', error);
            throw error;
        }
    }

    /**
     * Retry failed sync items
     */
    async retryFailed() {
        try {
            const failedItems = await dbManager.getSyncQueue('failed');
            
            for (const item of failedItems) {
                await dbManager.updateSyncQueueItem(item.id, {
                    status: 'pending',
                    attempts: 0
                });
            }

            console.log(`[SyncManager] Retrying ${failedItems.length} failed items`);

            // Trigger sync
            await this.syncAll();

            return failedItems.length;
        } catch (error) {
            console.error('[SyncManager] Failed to retry failed items:', error);
            throw error;
        }
    }

    /**
     * Schedule periodic sync
     */
    startPeriodicSync(intervalMinutes = 5) {
        console.log(`[SyncManager] Starting periodic sync every ${intervalMinutes} minutes`);

        this.syncInterval = setInterval(() => {
            if (navigator.onLine && !this.isSyncing) {
                console.log('[SyncManager] Running periodic sync...');
                this.syncAll().catch(error => {
                    console.error('[SyncManager] Periodic sync failed:', error);
                });
            }
        }, intervalMinutes * 60 * 1000);
    }

    /**
     * Stop periodic sync
     */
    stopPeriodicSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
            console.log('[SyncManager] Periodic sync stopped');
        }
    }

    /**
     * Cleanup
     */
    destroy() {
        this.stopPeriodicSync();
        console.log('[SyncManager] Destroyed');
    }
}

// Create and export singleton instance
const syncManager = new SyncManager();

// Initialize on load
if (typeof window !== 'undefined') {
    window.syncManager = syncManager;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            syncManager.init();
        });
    } else {
        syncManager.init();
    }
}

export default syncManager;