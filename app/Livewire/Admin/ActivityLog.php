<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ActivityLog extends Component
{
    use WithPagination;

    public $search = '';
    public $userFilter = '';
    public $actionFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    protected $queryString = ['search', 'userFilter', 'actionFilter', 'dateFrom', 'dateTo'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function updatingActionFilter()
    {
        $this->resetPage();
    }

    public function mount()
    {
        // Set default date range to last 7 days
        if (!$this->dateFrom) {
            $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        }
        if (!$this->dateTo) {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'userFilter', 'actionFilter', 'dateFrom', 'dateTo']);
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function exportLogs()
    {
        // In production, implement CSV export
        session()->flash('message', 'Export functionality will be implemented.');
    }

    private function getActivityLogs()
    {
        // This is a mock implementation. In production, you would use a proper activity log package
        // like spatie/laravel-activitylog or create your own activity_logs table
        
        return collect([
            [
                'id' => 1,
                'user_name' => 'John Doe',
                'action' => 'created',
                'model' => 'Product',
                'description' => 'Created product "Laptop"',
                'ip_address' => '192.168.1.1',
                'created_at' => now()->subHours(2),
            ],
            [
                'id' => 2,
                'user_name' => 'Jane Smith',
                'action' => 'updated',
                'model' => 'Order',
                'description' => 'Updated order #1234 status to completed',
                'ip_address' => '192.168.1.2',
                'created_at' => now()->subHours(5),
            ],
            [
                'id' => 3,
                'user_name' => 'Admin User',
                'action' => 'deleted',
                'model' => 'Customer',
                'description' => 'Deleted customer "Test Customer"',
                'ip_address' => '192.168.1.3',
                'created_at' => now()->subHours(8),
            ],
            [
                'id' => 4,
                'user_name' => 'John Doe',
                'action' => 'login',
                'model' => 'Auth',
                'description' => 'User logged in',
                'ip_address' => '192.168.1.1',
                'created_at' => now()->subHours(10),
            ],
            [
                'id' => 5,
                'user_name' => 'Jane Smith',
                'action' => 'created',
                'model' => 'Sale',
                'description' => 'Created new sale #5678',
                'ip_address' => '192.168.1.2',
                'created_at' => now()->subHours(12),
            ],
        ]);
    }

    public function render()
    {
        $logs = $this->getActivityLogs();

        // Apply filters
        if ($this->search) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['description'], $this->search) !== false ||
                       stripos($log['user_name'], $this->search) !== false;
            });
        }

        if ($this->userFilter) {
            $logs = $logs->where('user_name', $this->userFilter);
        }

        if ($this->actionFilter) {
            $logs = $logs->where('action', $this->actionFilter);
        }

        if ($this->dateFrom) {
            $logs = $logs->filter(function ($log) {
                return $log['created_at']->format('Y-m-d') >= $this->dateFrom;
            });
        }

        if ($this->dateTo) {
            $logs = $logs->filter(function ($log) {
                return $log['created_at']->format('Y-m-d') <= $this->dateTo;
            });
        }

        // Get unique users and actions for filters
        $users = $this->getActivityLogs()->pluck('user_name')->unique()->sort();
        $actions = $this->getActivityLogs()->pluck('action')->unique()->sort();

        // Paginate
        $currentPage = $this->getPage();
        $items = $logs->slice(($currentPage - 1) * $this->perPage, $this->perPage);
        
        return view('livewire.admin.activity-log', [
            'logs' => $items,
            'users' => $users,
            'actions' => $actions,
            'total' => $logs->count(),
        ]);
    }
}