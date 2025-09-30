<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WooCommerce\WooCommerceClient;
use App\Services\WooCommerce\ProductSyncService;
use App\Services\WooCommerce\OrderSyncService;
use App\Services\WooCommerce\CustomerSyncService;
use App\Services\WooCommerce\InventorySyncService;
use App\Jobs\SyncProductsFromWooCommerce;
use App\Jobs\SyncCustomersFromWooCommerce;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WooCommerceSyncController extends Controller
{
    /**
     * Test WooCommerce connection
     */
    public function testConnection(WooCommerceClient $client): JsonResponse
    {
        $result = $client->testConnection();

        return response()->json($result);
    }

    /**
     * Sync products from WooCommerce
     */
    public function syncProducts(Request $request): JsonResponse
    {
        $async = $request->boolean('async', true);

        if ($async) {
            // Dispatch job to queue
            SyncProductsFromWooCommerce::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'Product sync job dispatched to queue'
            ]);
        }

        // Sync immediately
        $syncService = app(ProductSyncService::class);
        $result = $syncService->importAll();

        return response()->json($result);
    }

    /**
     * Sync customers from WooCommerce
     */
    public function syncCustomers(Request $request): JsonResponse
    {
        $async = $request->boolean('async', true);

        if ($async) {
            // Dispatch job to queue
            SyncCustomersFromWooCommerce::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'Customer sync job dispatched to queue'
            ]);
        }

        // Sync immediately
        $syncService = app(CustomerSyncService::class);
        $result = $syncService->importAll();

        return response()->json($result);
    }

    /**
     * Sync inventory to WooCommerce
     */
    public function syncInventory(Request $request, InventorySyncService $syncService): JsonResponse
    {
        $result = $syncService->syncAllInventory();

        return response()->json($result);
    }

    /**
     * Sync all data
     */
    public function syncAll(Request $request): JsonResponse
    {
        $async = $request->boolean('async', true);

        if ($async) {
            // Dispatch all sync jobs
            SyncProductsFromWooCommerce::dispatch();
            SyncCustomersFromWooCommerce::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'All sync jobs dispatched to queue'
            ]);
        }

        // Sync immediately (not recommended for large datasets)
        $results = [];

        $productSync = app(ProductSyncService::class);
        $results['products'] = $productSync->importAll();

        $customerSync = app(CustomerSyncService::class);
        $results['customers'] = $customerSync->importAll();

        $inventorySync = app(InventorySyncService::class);
        $results['inventory'] = $inventorySync->syncAllInventory();

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Get sync status and recent logs
     */
    public function syncStatus(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);

        $recentLogs = SyncLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $stats = [
            'total_syncs' => SyncLog::count(),
            'successful_syncs' => SyncLog::where('status', 'success')->count(),
            'failed_syncs' => SyncLog::where('status', 'failed')->count(),
            'last_sync' => SyncLog::latest()->first(),
        ];

        // Get sync statistics by type
        $syncsByType = SyncLog::selectRaw('type, direction, COUNT(*) as count, MAX(created_at) as last_sync')
            ->groupBy('type', 'direction')
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'syncs_by_type' => $syncsByType,
            'recent_logs' => $recentLogs
        ]);
    }

    /**
     * Sync orders (not typically used - orders are usually exported from POS)
     */
    public function syncOrders(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Order sync from WooCommerce is not implemented. Orders are exported from POS to WooCommerce.'
        ], 400);
    }
}