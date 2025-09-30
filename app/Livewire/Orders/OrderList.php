<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Models\User;
use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class OrderList extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $status = '';
    public $paymentStatus = '';
    public $customerId = '';
    public $userId = '';
    public $startDate = '';
    public $endDate = '';
    public $syncStatus = '';
    
    // View options
    public $perPage = 20;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $viewMode = 'grid'; // grid or list
    
    // Statistics
    public $statistics = [];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'paymentStatus' => ['except' => ''],
        'viewMode' => ['except' => 'grid'],
    ];

    public function mount()
    {
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadStatistics();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingPaymentStatus()
    {
        $this->resetPage();
    }

    public function updatingCustomerId()
    {
        $this->resetPage();
    }

    public function updatingUserId()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->loadStatistics();
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->loadStatistics();
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'status',
            'paymentStatus',
            'customerId',
            'userId',
            'syncStatus',
        ]);
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadStatistics();
        $this->resetPage();
    }

    public function loadStatistics()
    {
        $query = Order::query();

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        $orders = $query->get();
        $completedOrders = $orders->where('status', 'completed');

        $this->statistics = [
            'total_orders' => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'total_revenue' => $completedOrders->sum('total'),
            'average_order_value' => $completedOrders->avg('total') ?? 0,
            'total_items' => $completedOrders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
            'unpaid_orders' => $orders->where('payment_status', 'pending')->count(),
            'unpaid_amount' => $orders->where('payment_status', 'pending')->sum('total'),
        ];
    }

    public function exportOrders()
    {
        $orders = $this->getOrdersQuery()->get();

        $csv = "Order Number,Customer,Date,Status,Payment Status,Items,Subtotal,Tax,Discount,Total\n";

        foreach ($orders as $order) {
            $customerName = $order->customer 
                ? $order->customer->first_name . ' ' . $order->customer->last_name 
                : 'Guest';
            
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%d,%.2f,%.2f,%.2f,%.2f\n",
                $order->order_number,
                $customerName,
                $order->created_at->format('Y-m-d H:i'),
                ucfirst($order->status),
                ucfirst($order->payment_status),
                $order->items->sum('quantity'),
                $order->subtotal,
                $order->tax_amount,
                $order->discount_amount,
                $order->total
            );
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'orders-' . now()->format('Y-m-d') . '.csv');
    }

    public function getOrdersQuery()
    {
        $query = Order::with(['customer', 'user', 'items'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('order_number', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', function ($q) {
                            $q->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->paymentStatus, fn($q) => $q->where('payment_status', $this->paymentStatus))
            ->when($this->customerId, fn($q) => $q->where('customer_id', $this->customerId))
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->when($this->syncStatus !== '', function ($q) {
                $q->where('is_synced', $this->syncStatus === '1');
            });

        return $query->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $orders = $this->getOrdersQuery()->paginate($this->perPage);
        
        $customers = Customer::orderBy('first_name')->get();
        $users = User::orderBy('name')->get();

        return view('livewire.orders.order-list', [
            'orders' => $orders,
            'customers' => $customers,
            'users' => $users,
        ]);
    }
}