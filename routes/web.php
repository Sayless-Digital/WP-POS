<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Installer Route - serves the standalone installer
Route::any('/install/{any?}', function () {
    require base_path('install/index.php');
    return response('', 200);
})->where('any', '.*');

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// POS Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/pos', \App\Livewire\Pos\PosTerminal::class)->name('pos.terminal');
    Route::get('/pos/checkout', \App\Livewire\Pos\Checkout::class)->name('pos.checkout');
});

// Product Management Routes
Route::middleware(['auth'])->prefix('products')->name('products.')->group(function () {
    Route::get('/', \App\Livewire\Products\ProductList::class)->name('index');
    Route::get('/create', \App\Livewire\Products\ProductForm::class)->name('create');
    Route::get('/{product}/edit', \App\Livewire\Products\ProductForm::class)->name('edit');
    Route::get('/{product}/variants', \App\Livewire\Products\ProductVariants::class)->name('variants');
    Route::get('/barcodes', \App\Livewire\Products\BarcodeManager::class)->name('barcodes');
    Route::get('/categories', \App\Livewire\Products\CategoryManager::class)->name('categories');
});

// Customer Management Routes
Route::middleware(['auth'])->prefix('customers')->name('customers.')->group(function () {
    Route::get('/', \App\Livewire\Customers\CustomerList::class)->name('index');
    Route::get('/{customer}/profile', \App\Livewire\Customers\CustomerProfile::class)->name('profile');
});

// Inventory Management Routes
Route::middleware(['auth'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', \App\Livewire\Inventory\StockList::class)->name('index');
    Route::get('/{inventory}/adjust', \App\Livewire\Inventory\StockAdjustment::class)->name('adjust');
    Route::get('/{inventory}/movements', \App\Livewire\Inventory\StockMovements::class)->name('movements');
    Route::get('/alerts', \App\Livewire\Inventory\LowStockAlert::class)->name('alerts');
});

// Order Management Routes
Route::middleware(['auth'])->prefix('orders')->name('orders.')->group(function () {
    Route::get('/', \App\Livewire\Orders\OrderList::class)->name('index');
    Route::get('/{order}/details', \App\Livewire\Orders\OrderDetails::class)->name('details');
    Route::get('/{order}/refund', \App\Livewire\Orders\OrderRefund::class)->name('refund');
    Route::get('/{order}/invoice', \App\Livewire\Orders\OrderInvoice::class)->name('invoice');
    Route::get('/reports', \App\Livewire\Orders\OrderReports::class)->name('reports');
});

// Cash Drawer Management Routes
Route::middleware(['auth'])->prefix('cash-drawer')->name('cash-drawer.')->group(function () {
    Route::get('/', \App\Livewire\CashDrawer\CashDrawerSession::class)->name('index');
    Route::get('/movements', \App\Livewire\CashDrawer\CashMovements::class)->name('movements');
    Route::get('/reports', \App\Livewire\CashDrawer\CashDrawerReport::class)->name('reports');
});

// Reporting & Analytics Routes
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/sales', \App\Livewire\Reports\SalesSummary::class)->name('sales');
    Route::get('/inventory', \App\Livewire\Reports\InventoryReport::class)->name('inventory');
    Route::get('/cashier', \App\Livewire\Reports\CashierReport::class)->name('cashier');
    Route::get('/products', \App\Livewire\Reports\ProductPerformance::class)->name('products');
});

// Settings & Configuration Routes
Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/general', \App\Livewire\Settings\GeneralSettings::class)->name('general');
    Route::get('/woocommerce', \App\Livewire\Settings\WooCommerceSync::class)->name('woocommerce');
    Route::get('/tax', \App\Livewire\Settings\TaxConfiguration::class)->name('tax');
});

// System Administration Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Admin\SystemDashboard::class)->name('dashboard');
    Route::get('/users', \App\Livewire\Admin\UserManagement::class)->name('users');
    Route::get('/roles', \App\Livewire\Admin\RoleManagement::class)->name('roles');
    Route::get('/activity-log', \App\Livewire\Admin\ActivityLog::class)->name('activity-log');
});

require __DIR__.'/auth.php';
