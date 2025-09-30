<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemDashboard extends Component
{
    public $systemInfo;
    public $databaseInfo;
    public $cacheInfo;
    public $storageInfo;

    public function mount()
    {
        $this->loadSystemInfo();
    }

    public function loadSystemInfo()
    {
        $this->systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];

        $this->databaseInfo = [
            'driver' => config('database.default'),
            'database' => config('database.connections.' . config('database.default') . '.database'),
            'tables_count' => $this->getTablesCount(),
            'total_records' => $this->getTotalRecords(),
        ];

        $this->cacheInfo = [
            'driver' => config('cache.default'),
            'enabled' => Cache::getStore() !== null,
        ];

        $this->storageInfo = [
            'disk' => config('filesystems.default'),
            'total_space' => $this->formatBytes(disk_total_space(storage_path())),
            'free_space' => $this->formatBytes(disk_free_space(storage_path())),
            'used_space' => $this->formatBytes(disk_total_space(storage_path()) - disk_free_space(storage_path())),
        ];
    }

    public function clearCache()
    {
        try {
            Cache::flush();
            session()->flash('message', 'Cache cleared successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error clearing cache: ' . $e->getMessage());
        }
    }

    public function optimizeDatabase()
    {
        try {
            // Run optimize commands
            \Artisan::call('optimize');
            session()->flash('message', 'Database optimized successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error optimizing database: ' . $e->getMessage());
        }
    }

    public function clearLogs()
    {
        try {
            $logPath = storage_path('logs');
            $files = glob($logPath . '/*.log');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            session()->flash('message', 'Logs cleared successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error clearing logs: ' . $e->getMessage());
        }
    }

    public function runMaintenance()
    {
        try {
            \Artisan::call('down');
            session()->flash('message', 'Maintenance mode enabled.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error enabling maintenance mode: ' . $e->getMessage());
        }
    }

    public function exitMaintenance()
    {
        try {
            \Artisan::call('up');
            session()->flash('message', 'Maintenance mode disabled.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error disabling maintenance mode: ' . $e->getMessage());
        }
    }

    private function getTablesCount()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            return count($tables);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTotalRecords()
    {
        try {
            return User::count() + Product::count() + Order::count() + Customer::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function render()
    {
        $stats = [
            'users' => User::count(),
            'products' => Product::count(),
            'orders' => Order::count(),
            'customers' => Customer::count(),
            'sales_today' => Order::whereDate('created_at', today())->sum('total'),
            'orders_today' => Order::whereDate('created_at', today())->count(),
        ];

        return view('livewire.admin.system-dashboard', [
            'stats' => $stats,
        ]);
    }
}