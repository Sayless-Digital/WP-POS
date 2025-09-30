<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseHistory extends Component
{
    use WithPagination;

    public $customerId;
    public Customer $customer;
    
    // Filters
    public $search = '';
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    
    // Selected order for details
    public $selectedOrderId = null;
    public $showOrderDetails = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    /**
     * Mount the component
     */
    public function mount($customerId)
    {
        $this->customerId = $customerId;
        $this->customer = Customer::findOrFail($customerId);
    }

    /**
     * Reset pagination when filters change
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    /**
     * Sort orders by column
     */
    public function sortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    /**
     * View order details
     */
    public function viewOrder($orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->showOrderDetails = true;
    }

    /**
     * Close order details
     */
    public function closeOrderDetails()
    {
        $this->showOrderDetails = false;
        $this->selectedOrderId = null;
    }

    /**
     * Export orders
     */
    public function exportOrders()
    {
        $this->dispatch('export-orders', ['customerId' => $this->customerId]);
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Export functionality will be implemented'
        ]);
    }

    /**
     * Get orders query with filters
     */
    protected function getOrdersQuery()
    {
        $query = Order::where('customer_id', $this->customerId)
            ->with(['items.product', 'items.variant', 'payments']);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                  ->orWhereHas('items.product', function ($q) {
                      $q->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply date filters
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query;
    }

    /**
     * Get order statistics
     */
    public function getStatisticsProperty()
    {
        $query = Order::where('customer_id', $this->customerId);

        // Apply date filters to statistics
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $orders = $query->get();

        return [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->where('status', 'completed')->sum('total'),
            'average_order' => $orders->where('status', 'completed')->avg('total') ?? 0,
            'completed' => $orders->where('status', 'completed')->count(),
            'pending' => $orders->where('status', 'pending')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Get selected order
     */
    public function getSelectedOrderProperty()
    {
        if (!$this->selectedOrderId) {
            return null;
        }

        return Order::with(['items.product', 'items.variant', 'payments', 'refunds'])
            ->find($this->selectedOrderId);
    }

    /**
     * Render the component
     */
    public function render()
    {
        $orders = $this->getOrdersQuery()->paginate($this->perPage);

        return view('livewire.customers.purchase-history', [
            'orders' => $orders,
            'statistics' => $this->statistics,
            'selectedOrder' => $this->selectedOrder,
        ]);
    }
}