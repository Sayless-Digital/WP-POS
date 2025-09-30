<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\CashDrawerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
        ]);
    });

    // Authentication
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes (require authentication)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/tokens', [AuthController::class, 'tokens']);
    Route::delete('/tokens/{tokenId}', [AuthController::class, 'revokeToken']);
    
    // User info (legacy endpoint)
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['roles', 'permissions']),
        ]);
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/search-barcode', [ProductController::class, 'searchByBarcode']);
        Route::get('/low-stock', [ProductController::class, 'lowStock']);
        Route::post('/bulk-update-status', [ProductController::class, 'bulkUpdateStatus']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::patch('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/today', [OrderController::class, 'today']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::patch('/{id}', [OrderController::class, 'update']);
        Route::post('/{id}/complete', [OrderController::class, 'complete']);
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
        Route::post('/{id}/payment', [OrderController::class, 'addPayment']);
        Route::post('/{id}/refund', [OrderController::class, 'refund']);
    });

    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/search', [CustomerController::class, 'search']);
        Route::get('/vip', [CustomerController::class, 'vip']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::patch('/{id}', [CustomerController::class, 'update']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
        Route::post('/{id}/loyalty-points/add', [CustomerController::class, 'addLoyaltyPoints']);
        Route::post('/{id}/loyalty-points/redeem', [CustomerController::class, 'redeemLoyaltyPoints']);
        Route::get('/{id}/purchase-history', [CustomerController::class, 'purchaseHistory']);
    });

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('/out-of-stock', [InventoryController::class, 'outOfStock']);
        Route::get('/{type}/{id}', [InventoryController::class, 'show']);
        Route::post('/{type}/{id}/adjust', [InventoryController::class, 'adjust']);
        Route::post('/{type}/{id}/physical-count', [InventoryController::class, 'physicalCount']);
        Route::post('/{type}/{id}/reserve', [InventoryController::class, 'reserve']);
        Route::post('/{type}/{id}/release', [InventoryController::class, 'release']);
        Route::get('/{type}/{id}/movements', [InventoryController::class, 'movements']);
    });

    // Cash Drawer
    Route::prefix('cash-drawer')->group(function () {
        Route::get('/', [CashDrawerController::class, 'index']);
        Route::post('/open', [CashDrawerController::class, 'open']);
        Route::get('/today', [CashDrawerController::class, 'today']);
        Route::get('/with-discrepancies', [CashDrawerController::class, 'withDiscrepancies']);
        Route::get('/current/{userId}', [CashDrawerController::class, 'current']);
        Route::get('/{id}', [CashDrawerController::class, 'show']);
        Route::post('/{id}/close', [CashDrawerController::class, 'close']);
        Route::post('/{id}/movement', [CashDrawerController::class, 'addMovement']);
        Route::get('/{id}/movements', [CashDrawerController::class, 'movements']);
        Route::get('/{id}/summary', [CashDrawerController::class, 'summary']);
    });

    // Product Categories (simple CRUD)
    Route::apiResource('categories', \App\Http\Controllers\Api\ProductCategoryController::class);

    // Product Variants (simple CRUD)
    Route::apiResource('variants', \App\Http\Controllers\Api\ProductVariantController::class);

    // Customer Groups (simple CRUD)
    Route::apiResource('customer-groups', \App\Http\Controllers\Api\CustomerGroupController::class);

    // Barcodes (simple CRUD)
    Route::apiResource('barcodes', \App\Http\Controllers\Api\BarcodeController::class);

    // Payments (read-only)
    Route::prefix('payments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PaymentController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\PaymentController::class, 'show']);
    });

    // Refunds (read-only)
    Route::prefix('refunds')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\RefundController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\RefundController::class, 'show']);
    });

    // Held Orders
    Route::prefix('held-orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HeldOrderController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\HeldOrderController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\HeldOrderController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\HeldOrderController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\HeldOrderController::class, 'destroy']);
        Route::post('/{id}/convert', [\App\Http\Controllers\Api\HeldOrderController::class, 'convertToOrder']);
    });

    // Sync Queue
    Route::prefix('sync-queue')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SyncQueueController::class, 'index']);
        Route::get('/pending', [\App\Http\Controllers\Api\SyncQueueController::class, 'pending']);
        Route::get('/failed', [\App\Http\Controllers\Api\SyncQueueController::class, 'failed']);
        Route::post('/{id}/retry', [\App\Http\Controllers\Api\SyncQueueController::class, 'retry']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\SyncQueueController::class, 'destroy']);
    });

    // Sync Logs (read-only)
    Route::prefix('sync-logs')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SyncLogController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\SyncLogController::class, 'show']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/sales-summary', [\App\Http\Controllers\Api\ReportController::class, 'salesSummary']);
        Route::get('/sales-by-product', [\App\Http\Controllers\Api\ReportController::class, 'salesByProduct']);
        Route::get('/sales-by-category', [\App\Http\Controllers\Api\ReportController::class, 'salesByCategory']);
        Route::get('/sales-by-cashier', [\App\Http\Controllers\Api\ReportController::class, 'salesByCashier']);
        Route::get('/inventory-value', [\App\Http\Controllers\Api\ReportController::class, 'inventoryValue']);
        Route::get('/top-customers', [\App\Http\Controllers\Api\ReportController::class, 'topCustomers']);
        Route::get('/top-products', [\App\Http\Controllers\Api\ReportController::class, 'topProducts']);
    });
});

// Rate limiting for API routes
Route::middleware(['throttle:api'])->group(function () {
    // Add rate-limited routes here if needed
});
