import './bootstrap';

// Import offline functionality
import './offline/indexed-db.js';
import './offline/connection-monitor.js';
import './offline/sync-manager.js';

// Register service worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then((registration) => {
                console.log('[PWA] Service Worker registered:', registration.scope);
                
                // Check for updates periodically
                setInterval(() => {
                    registration.update();
                }, 60000); // Check every minute
                
                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New service worker available
                            console.log('[PWA] New version available');
                            
                            // Notify user
                            if (confirm('A new version is available. Reload to update?')) {
                                newWorker.postMessage({ type: 'SKIP_WAITING' });
                                window.location.reload();
                            }
                        }
                    });
                });
            })
            .catch((error) => {
                console.error('[PWA] Service Worker registration failed:', error);
            });
    });
    
    // Handle service worker controller change
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        console.log('[PWA] Service Worker controller changed');
        window.location.reload();
    });
}

// PWA install prompt
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    console.log('[PWA] Install prompt available');
    e.preventDefault();
    deferredPrompt = e;
    
    // Show custom install button if exists
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'block';
        
        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('[PWA] Install outcome:', outcome);
                deferredPrompt = null;
                installButton.style.display = 'none';
            }
        });
    }
});

window.addEventListener('appinstalled', () => {
    console.log('[PWA] App installed successfully');
    deferredPrompt = null;
});

// Handle offline notifications
window.addEventListener('pos-notification', (event) => {
    const { type, message } = event.detail;
    
    // Try to use Livewire notification if available
    if (window.Livewire) {
        window.Livewire.dispatch('notify', { type, message });
    } else {
        // Fallback to console
        console.log(`[Notification] ${type}: ${message}`);
    }
});

// Expose offline utilities globally
window.offlineMode = {
    isOnline: () => window.connectionMonitor?.isOnline ?? navigator.onLine,
    getStatus: () => window.connectionMonitor?.getStatus() ?? { isOnline: navigator.onLine },
    forceSync: () => window.syncManager?.syncAll(),
    getSyncStatus: () => window.syncManager?.getStatus(),
    getDbStats: () => window.dbManager?.getStats()
};

console.log('[PWA] Offline mode initialized');
