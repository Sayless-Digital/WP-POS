<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\SyncLog;
use App\Models\SyncQueue;
use App\Services\WooCommerce\WooCommerceClient;
use App\Services\WooCommerce\ProductSyncService;
use App\Services\WooCommerce\OrderSyncService;
use App\Services\WooCommerce\CustomerSyncService;
use App\Services\WooCommerce\InventorySyncService;
use App\Jobs\SyncProductsFromWooCommerce;
use App\Jobs\SyncCustomersFromWooCommerce;
use Illuminate\Support\Facades\Cache;

class WooCommerceSync extends Component
{
    public $woocommerceUrl;
    public $consumerKey;
    public $consumerSecret;
    public $syncEnabled;
    public $autoSync;
    public $syncInterval;
    public $syncProducts;
    public $syncOrders;
    public $syncCustomers;
    public $syncInventory;
    public $lastSyncTime;
    public $connectionStatus = 'unknown';
    public $isTesting = false;
    public $isSyncing = false;

    protected $rules = [
        'woocommerceUrl' => 'required|url',
        'consumerKey' => 'required|string',
        'consumerSecret' => 'required|string',
        'syncEnabled' => 'boolean',
        'autoSync' => 'boolean',
        'syncInterval' => 'required|integer|min:5|max:1440',
        'syncProducts' => 'boolean',
        'syncOrders' => 'boolean',
        'syncCustomers' => 'boolean',
        'syncInventory' => 'boolean',
    ];

    public function mount()
    {
        $this->loadSettings();
        $this->lastSyncTime = Cache::get('last_sync_time');
    }

    public function loadSettings()
    {
        $this->woocommerceUrl = config('services.woocommerce.url', '');
        $this->consumerKey = config('services.woocommerce.consumer_key', '');
        $this->consumerSecret = config('services.woocommerce.consumer_secret', '');
        $this->syncEnabled = config('services.woocommerce.sync_enabled', false);
        $this->autoSync = config('services.woocommerce.auto_sync', false);
        $this->syncInterval = config('services.woocommerce.sync_interval', 30);
        $this->syncProducts = config('services.woocommerce.sync_products', true);
        $this->syncOrders = config('services.woocommerce.sync_orders', true);
        $this->syncCustomers = config('services.woocommerce.sync_customers', true);
        $this->syncInventory = config('services.woocommerce.sync_inventory', true);
    }

    public function testConnection()
    {
        $this->isTesting = true;
        
        try {
            $client = app(WooCommerceClient::class);
            $result = $client->testConnection();

            if ($result['success']) {
                $this->connectionStatus = 'success';
                session()->flash('message', 'Connection successful! WooCommerce version: ' .
                    ($result['data']['environment']['version'] ?? 'Unknown'));
            } else {
                $this->connectionStatus = 'error';
                session()->flash('error', 'Connection failed: ' . $result['message']);
            }
        } catch (\Exception $e) {
            $this->connectionStatus = 'error';
            session()->flash('error', 'Connection failed: ' . $e->getMessage());
        }

        $this->isTesting = false;
    }

    public function save()
    {
        $this->validate();

        // Update .env file
        $this->updateEnvFile([
            'WOOCOMMERCE_URL' => $this->woocommerceUrl,
            'WOOCOMMERCE_CONSUMER_KEY' => $this->consumerKey,
            'WOOCOMMERCE_CONSUMER_SECRET' => $this->consumerSecret,
            'WOOCOMMERCE_SYNC_ENABLED' => $this->syncEnabled ? 'true' : 'false',
            'WOOCOMMERCE_AUTO_SYNC' => $this->autoSync ? 'true' : 'false',
            'WOOCOMMERCE_SYNC_INTERVAL' => $this->syncInterval,
            'WOOCOMMERCE_SYNC_PRODUCTS' => $this->syncProducts ? 'true' : 'false',
            'WOOCOMMERCE_SYNC_ORDERS' => $this->syncOrders ? 'true' : 'false',
            'WOOCOMMERCE_SYNC_CUSTOMERS' => $this->syncCustomers ? 'true' : 'false',
            'WOOCOMMERCE_SYNC_INVENTORY' => $this->syncInventory ? 'true' : 'false',
        ]);

        session()->flash('message', 'WooCommerce settings saved successfully.');
    }

    public function syncNow()
    {
        if (!$this->syncEnabled) {
            session()->flash('error', 'Sync is not enabled. Please enable sync first.');
            return;
        }

        $this->isSyncing = true;

        try {
            // Dispatch sync jobs to queue
            if ($this->syncProducts) {
                SyncProductsFromWooCommerce::dispatch();
            }

            if ($this->syncCustomers) {
                SyncCustomersFromWooCommerce::dispatch();
            }

            if ($this->syncInventory) {
                $inventoryService = app(InventorySyncService::class);
                $inventoryService->syncAllInventory();
            }

            Cache::put('last_sync_time', now(), 3600);
            $this->lastSyncTime = now();

            session()->flash('message', 'Sync jobs dispatched successfully. Processing in background...');
        } catch (\Exception $e) {
            session()->flash('error', 'Sync failed: ' . $e->getMessage());
        }

        $this->isSyncing = false;
    }

    public function syncProducts()
    {
        try {
            SyncProductsFromWooCommerce::dispatch();
            session()->flash('message', 'Product sync job dispatched.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to dispatch product sync: ' . $e->getMessage());
        }
    }

    public function syncCustomers()
    {
        try {
            SyncCustomersFromWooCommerce::dispatch();
            session()->flash('message', 'Customer sync job dispatched.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to dispatch customer sync: ' . $e->getMessage());
        }
    }

    public function syncInventory()
    {
        try {
            $inventoryService = app(InventorySyncService::class);
            $result = $inventoryService->syncAllInventory();
            
            if ($result['success']) {
                session()->flash('message', "Inventory synced: {$result['stats']['success']} successful, {$result['stats']['failed']} failed");
            } else {
                session()->flash('error', 'Inventory sync failed');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to sync inventory: ' . $e->getMessage());
        }
    }

    public function clearSyncQueue()
    {
        SyncQueue::where('status', 'pending')->delete();
        session()->flash('message', 'Sync queue cleared successfully.');
    }

    private function updateEnvFile(array $data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    public function render()
    {
        $syncLogs = SyncLog::latest()->take(10)->get();
        $queuedItems = SyncQueue::where('status', 'pending')->count();
        $failedItems = SyncQueue::where('status', 'failed')->count();

        return view('livewire.settings.woo-commerce-sync', [
            'syncLogs' => $syncLogs,
            'queuedItems' => $queuedItems,
            'failedItems' => $failedItems,
        ]);
    }
}