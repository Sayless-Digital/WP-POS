/**
 * WP-POS Service Worker
 * Handles offline caching, background sync, and PWA functionality
 */

const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `wp-pos-cache-${CACHE_VERSION}`;
const DATA_CACHE_NAME = `wp-pos-data-${CACHE_VERSION}`;

// Assets to cache immediately on install
const STATIC_ASSETS = [
    '/',
    '/pos',
    '/offline.html',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
];

// API routes that should be cached
const API_CACHE_ROUTES = [
    '/api/products/cache',
    '/api/customers',
    '/api/settings',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('[Service Worker] Installation complete');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[Service Worker] Installation failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activating...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME && cacheName !== DATA_CACHE_NAME) {
                            console.log('[Service Worker] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[Service Worker] Activation complete');
                return self.clients.claim();
            })
    );
});

// Fetch event - network first, then cache fallback
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Handle API requests
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(handleApiRequest(request));
        return;
    }
    
    // Handle static assets
    event.respondWith(handleStaticRequest(request));
});

/**
 * Handle API requests with network-first strategy
 */
async function handleApiRequest(request) {
    try {
        // Try network first
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(DATA_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('[Service Worker] Network request failed, trying cache:', request.url);
        
        // Fallback to cache
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline response for critical endpoints
        return new Response(
            JSON.stringify({
                error: 'Offline',
                message: 'You are currently offline. Some features may be limited.',
                cached: false
            }),
            {
                status: 503,
                statusText: 'Service Unavailable',
                headers: new Headers({
                    'Content-Type': 'application/json',
                    'X-Offline': 'true'
                })
            }
        );
    }
}

/**
 * Handle static asset requests with cache-first strategy
 */
async function handleStaticRequest(request) {
    // Try cache first
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        // Try network
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('[Service Worker] Failed to fetch:', request.url);
        
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            const offlineResponse = await caches.match('/offline.html');
            if (offlineResponse) {
                return offlineResponse;
            }
        }
        
        // Return generic error response
        return new Response('Offline', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

// Background sync event - sync pending orders when online
self.addEventListener('sync', (event) => {
    console.log('[Service Worker] Background sync triggered:', event.tag);
    
    if (event.tag === 'sync-orders') {
        event.waitUntil(syncPendingOrders());
    } else if (event.tag === 'sync-inventory') {
        event.waitUntil(syncInventory());
    }
});

/**
 * Sync pending orders to server
 */
async function syncPendingOrders() {
    try {
        console.log('[Service Worker] Syncing pending orders...');
        
        // Get pending orders from IndexedDB
        const db = await openDatabase();
        const orders = await getPendingOrders(db);
        
        console.log(`[Service Worker] Found ${orders.length} pending orders`);
        
        // Sync each order
        for (const order of orders) {
            try {
                const response = await fetch('/api/orders/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(order)
                });
                
                if (response.ok) {
                    console.log('[Service Worker] Order synced successfully:', order.id);
                    await removePendingOrder(db, order.id);
                    
                    // Notify clients
                    await notifyClients({
                        type: 'order-synced',
                        orderId: order.id
                    });
                } else {
                    console.error('[Service Worker] Order sync failed:', response.status);
                }
            } catch (error) {
                console.error('[Service Worker] Error syncing order:', error);
            }
        }
        
        console.log('[Service Worker] Order sync complete');
    } catch (error) {
        console.error('[Service Worker] Background sync failed:', error);
        throw error;
    }
}

/**
 * Sync inventory updates
 */
async function syncInventory() {
    try {
        console.log('[Service Worker] Syncing inventory...');
        
        // Refresh product cache
        const response = await fetch('/api/products/cache');
        
        if (response.ok) {
            const products = await response.json();
            const cache = await caches.open(DATA_CACHE_NAME);
            
            await cache.put(
                '/api/products/cache',
                new Response(JSON.stringify(products), {
                    headers: { 'Content-Type': 'application/json' }
                })
            );
            
            console.log('[Service Worker] Inventory synced successfully');
            
            // Notify clients
            await notifyClients({
                type: 'inventory-synced',
                count: products.length
            });
        }
    } catch (error) {
        console.error('[Service Worker] Inventory sync failed:', error);
        throw error;
    }
}

/**
 * Open IndexedDB database
 */
function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('POSDatabase', 1);
        
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Get pending orders from IndexedDB
 */
function getPendingOrders(db) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pendingOrders'], 'readonly');
        const store = transaction.objectStore('pendingOrders');
        const request = store.getAll();
        
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Remove pending order from IndexedDB
 */
function removePendingOrder(db, orderId) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pendingOrders'], 'readwrite');
        const store = transaction.objectStore('pendingOrders');
        const request = store.delete(orderId);
        
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

/**
 * Notify all clients of an event
 */
async function notifyClients(message) {
    const clients = await self.clients.matchAll({ includeUncontrolled: true });
    
    clients.forEach((client) => {
        client.postMessage(message);
    });
}

// Push notification event
self.addEventListener('push', (event) => {
    console.log('[Service Worker] Push notification received');
    
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'WP-POS Notification';
    const options = {
        body: data.body || 'You have a new notification',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/icon-96x96.png',
        data: data.data || {},
        actions: data.actions || []
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification clicked');
    
    event.notification.close();
    
    const urlToOpen = event.notification.data.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if there's already a window open
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Message event - handle messages from clients
self.addEventListener('message', (event) => {
    console.log('[Service Worker] Message received:', event.data);
    
    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(CACHE_NAME)
                .then((cache) => cache.addAll(event.data.urls))
        );
    } else if (event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.keys()
                .then((cacheNames) => Promise.all(
                    cacheNames.map((cacheName) => caches.delete(cacheName))
                ))
        );
    }
});

console.log('[Service Worker] Script loaded');