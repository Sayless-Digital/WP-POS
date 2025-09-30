<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Services\CustomerService;
use Livewire\Component;

class CustomerProfile extends Component
{
    public Customer $customer;
    public $customerId;
    
    // Modals
    public $showEditModal = false;
    public $showLoyaltyModal = false;
    public $showPurchaseHistory = false;
    
    // Loyalty points management
    public $loyaltyAction = 'add'; // 'add' or 'redeem'
    public $loyaltyPoints = 0;
    public $loyaltyReason = '';

    protected $customerService;

    protected $listeners = [
        'customer-updated' => 'refreshCustomer',
    ];

    /**
     * Mount the component
     */
    public function mount($customerId)
    {
        $this->customerService = app(CustomerService::class);
        $this->customerId = $customerId;
        $this->loadCustomer();
    }

    /**
     * Load customer data
     */
    public function loadCustomer()
    {
        $this->customer = Customer::with(['customerGroup', 'orders'])
            ->findOrFail($this->customerId);
    }

    /**
     * Refresh customer data
     */
    public function refreshCustomer()
    {
        $this->loadCustomer();
    }

    /**
     * Open edit modal
     */
    public function editCustomer()
    {
        $this->showEditModal = true;
    }

    /**
     * Close edit modal
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->refreshCustomer();
    }

    /**
     * Open loyalty points modal
     */
    public function manageLoyaltyPoints()
    {
        $this->showLoyaltyModal = true;
        $this->loyaltyAction = 'add';
        $this->loyaltyPoints = 0;
        $this->loyaltyReason = '';
    }

    /**
     * Close loyalty modal
     */
    public function closeLoyaltyModal()
    {
        $this->showLoyaltyModal = false;
        $this->reset(['loyaltyPoints', 'loyaltyReason', 'loyaltyAction']);
    }

    /**
     * Award loyalty points
     */
    public function awardLoyaltyPoints()
    {
        $this->validate([
            'loyaltyPoints' => 'required|integer|min:1',
            'loyaltyReason' => 'nullable|string|max:255',
        ]);

        try {
            if ($this->loyaltyAction === 'add') {
                $this->customerService->awardLoyaltyPoints(
                    $this->customer,
                    $this->loyaltyPoints,
                    $this->loyaltyReason
                );
                $message = $this->loyaltyPoints . ' loyalty points awarded successfully';
            } else {
                $this->customerService->redeemLoyaltyPoints(
                    $this->customer,
                    $this->loyaltyPoints
                );
                $message = $this->loyaltyPoints . ' loyalty points redeemed successfully';
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message
            ]);

            $this->closeLoyaltyModal();
            $this->refreshCustomer();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle purchase history
     */
    public function togglePurchaseHistory()
    {
        $this->showPurchaseHistory = !$this->showPurchaseHistory;
    }

    /**
     * Delete customer
     */
    public function deleteCustomer()
    {
        if ($this->customer->orders()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete customer with existing orders'
            ]);
            return;
        }

        try {
            $this->customer->delete();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Customer deleted successfully'
            ]);

            return redirect()->route('customers.index');

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error deleting customer: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get customer statistics
     */
    public function getStatisticsProperty()
    {
        return $this->customerService->getCustomerStatistics($this->customer);
    }

    /**
     * Get recent orders
     */
    public function getRecentOrdersProperty()
    {
        return $this->customer->orders()
            ->with(['items', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Calculate loyalty discount
     */
    public function getLoyaltyDiscountProperty()
    {
        if ($this->loyaltyPoints <= 0) {
            return 0;
        }
        return $this->customer->calculateLoyaltyDiscount($this->loyaltyPoints);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.customers.customer-profile', [
            'statistics' => $this->statistics,
            'recentOrders' => $this->recentOrders,
        ]);
    }
}