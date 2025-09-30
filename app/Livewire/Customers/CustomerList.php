<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;

    // Search and filters
    public $search = '';
    public $groupFilter = '';
    public $statusFilter = '';
    public $sortBy = 'first_name';
    public $sortDirection = 'asc';
    
    // View options
    public $viewMode = 'list'; // 'grid' or 'list'
    public $perPage = 15;
    
    // Bulk actions
    public $selectedCustomers = [];
    public $selectAll = false;
    
    // Modals
    public $showFilters = false;
    public $showBulkActions = false;
    public $showCustomerForm = false;
    public $editingCustomerId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'groupFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'viewMode' => ['except' => 'list'],
    ];

    protected $listeners = [
        'customer-created' => '$refresh',
        'customer-updated' => '$refresh',
        'customer-deleted' => '$refresh',
    ];

    /**
     * Reset pagination when search or filters change
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingGroupFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Toggle view mode between grid and list
     */
    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    /**
     * Sort customers by column
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
        $this->reset(['search', 'groupFilter', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * Open customer form for creating new customer
     */
    public function createCustomer()
    {
        $this->editingCustomerId = null;
        $this->showCustomerForm = true;
    }

    /**
     * Open customer form for editing
     */
    public function editCustomer($customerId)
    {
        $this->editingCustomerId = $customerId;
        $this->showCustomerForm = true;
    }

    /**
     * View customer profile
     */
    public function viewCustomer($customerId)
    {
        return redirect()->route('customers.profile', $customerId);
    }

    /**
     * Delete customer
     */
    public function deleteCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Customer not found'
            ]);
            return;
        }

        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete customer with existing orders'
            ]);
            return;
        }

        $customer->delete();
        
        $this->dispatch('customer-deleted');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * Toggle customer selection
     */
    public function toggleCustomerSelection($customerId)
    {
        if (in_array($customerId, $this->selectedCustomers)) {
            $this->selectedCustomers = array_diff($this->selectedCustomers, [$customerId]);
        } else {
            $this->selectedCustomers[] = $customerId;
        }
    }

    /**
     * Select all customers on current page
     */
    public function selectAllOnPage()
    {
        $customerIds = $this->getCustomersQuery()->pluck('id')->toArray();
        $this->selectedCustomers = array_unique(array_merge($this->selectedCustomers, $customerIds));
        $this->selectAll = true;
    }

    /**
     * Deselect all customers
     */
    public function deselectAll()
    {
        $this->selectedCustomers = [];
        $this->selectAll = false;
    }

    /**
     * Bulk assign to customer group
     */
    public function bulkAssignGroup($groupId)
    {
        if (empty($this->selectedCustomers)) {
            return;
        }

        Customer::whereIn('id', $this->selectedCustomers)
            ->update(['customer_group_id' => $groupId ?: null]);
        
        $this->dispatch('customer-updated');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedCustomers) . ' customers updated successfully'
        ]);
        
        $this->deselectAll();
    }

    /**
     * Bulk delete customers
     */
    public function bulkDelete()
    {
        if (empty($this->selectedCustomers)) {
            return;
        }

        // Check if any customers have order history
        $customersWithOrders = Customer::whereIn('id', $this->selectedCustomers)
            ->whereHas('orders')
            ->count();

        if ($customersWithOrders > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete customers with order history'
            ]);
            return;
        }

        Customer::whereIn('id', $this->selectedCustomers)->delete();
        
        $this->dispatch('customer-deleted');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedCustomers) . ' customers deleted successfully'
        ]);
        
        $this->deselectAll();
    }

    /**
     * Export selected customers
     */
    public function exportSelected()
    {
        if (empty($this->selectedCustomers)) {
            return;
        }

        // Dispatch event to handle export
        $this->dispatch('export-customers', ['customerIds' => $this->selectedCustomers]);
    }

    /**
     * Award loyalty points to selected customers
     */
    public function bulkAwardPoints($points)
    {
        if (empty($this->selectedCustomers) || $points <= 0) {
            return;
        }

        Customer::whereIn('id', $this->selectedCustomers)
            ->increment('loyalty_points', $points);
        
        $this->dispatch('customer-updated');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $points . ' loyalty points awarded to ' . count($this->selectedCustomers) . ' customers'
        ]);
        
        $this->deselectAll();
    }

    /**
     * Get customers query with filters
     */
    protected function getCustomersQuery()
    {
        $query = Customer::query()
            ->with(['customerGroup', 'orders']);

        // Apply search
        if ($this->search) {
            $query->search($this->search);
        }

        // Apply customer group filter
        if ($this->groupFilter) {
            $query->where('customer_group_id', $this->groupFilter);
        }

        // Apply status filter
        if ($this->statusFilter === 'vip') {
            $query->vip();
        } elseif ($this->statusFilter === 'active') {
            $query->active();
        } elseif ($this->statusFilter === 'inactive') {
            $query->whereDoesntHave('orders', function ($q) {
                $q->where('created_at', '>=', now()->subDays(90));
            });
        } elseif ($this->statusFilter === 'with_points') {
            $query->withLoyaltyPoints();
        }

        // Apply sorting
        if ($this->sortBy === 'total_spent') {
            $query->orderBySpent($this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query;
    }

    /**
     * Get customer groups for filter dropdown
     */
    public function getCustomerGroupsProperty()
    {
        return CustomerGroup::orderBy('name')->get();
    }

    /**
     * Get filter counts
     */
    public function getFilterCountsProperty()
    {
        return [
            'all' => Customer::count(),
            'vip' => Customer::vip()->count(),
            'active' => Customer::active()->count(),
            'inactive' => Customer::whereDoesntHave('orders', function ($q) {
                $q->where('created_at', '>=', now()->subDays(90));
            })->count(),
            'with_points' => Customer::withLoyaltyPoints()->count(),
        ];
    }

    /**
     * Get statistics
     */
    public function getStatisticsProperty()
    {
        return [
            'total_customers' => Customer::count(),
            'total_spent' => Customer::sum('total_spent'),
            'average_spent' => Customer::avg('total_spent'),
            'total_loyalty_points' => Customer::sum('loyalty_points'),
        ];
    }

    /**
     * Render the component
     */
    public function render()
    {
        $customers = $this->getCustomersQuery()->paginate($this->perPage);

        return view('livewire.customers.customer-list', [
            'customers' => $customers,
            'customerGroups' => $this->customerGroups,
            'filterCounts' => $this->filterCounts,
            'statistics' => $this->statistics,
        ]);
    }
}