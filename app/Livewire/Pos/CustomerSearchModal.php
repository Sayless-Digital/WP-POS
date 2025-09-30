<?php

namespace App\Livewire\Pos;

use App\Models\Customer;
use App\Services\CustomerService;
use Livewire\Component;
use Livewire\Attributes\On;

class CustomerSearchModal extends Component
{
    // Search state
    public string $searchQuery = '';
    public array $searchResults = [];
    public bool $showResults = false;
    
    // Selected customer
    public ?int $selectedCustomerId = null;
    
    // New customer form
    public bool $showNewCustomerForm = false;
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public string $phone = '';
    
    // Services
    protected CustomerService $customerService;
    
    public function boot(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }
    
    /**
     * Search customers as user types
     */
    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            $this->showResults = false;
            return;
        }
        
        $this->searchResults = Customer::query()
            ->where(function ($query) {
                $query->where('first_name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('last_name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('phone', 'like', '%' . $this->searchQuery . '%');
            })
            ->limit(10)
            ->get()
            ->toArray();
        
        $this->showResults = true;
    }
    
    /**
     * Select a customer
     */
    public function selectCustomer(int $customerId)
    {
        $this->selectedCustomerId = $customerId;
        $this->dispatch('customer-selected', customerId: $customerId);
        $this->resetSearch();
    }
    
    /**
     * Remove selected customer (walk-in customer)
     */
    public function removeCustomer()
    {
        $this->selectedCustomerId = null;
        $this->dispatch('customer-selected', customerId: null);
    }
    
    /**
     * Show new customer form
     */
    public function showCreateForm()
    {
        $this->showNewCustomerForm = true;
        $this->resetSearch();
    }
    
    /**
     * Hide new customer form
     */
    public function hideCreateForm()
    {
        $this->showNewCustomerForm = false;
        $this->resetNewCustomerForm();
    }
    
    /**
     * Create new customer
     */
    public function createCustomer()
    {
        $this->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
        ]);
        
        try {
            $customer = $this->customerService->createCustomer([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'phone' => $this->phone,
            ]);
            
            $this->selectCustomer($customer->id);
            $this->hideCreateForm();
            $this->dispatch('customer-created', message: 'Customer created successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to create customer: ' . $e->getMessage());
        }
    }
    
    /**
     * Reset search
     */
    protected function resetSearch()
    {
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->showResults = false;
    }
    
    /**
     * Reset new customer form
     */
    protected function resetNewCustomerForm()
    {
        $this->firstName = '';
        $this->lastName = '';
        $this->email = '';
        $this->phone = '';
    }
    
    /**
     * Close modal
     */
    public function closeModal()
    {
        $this->dispatch('close-customer-modal');
        $this->resetSearch();
        $this->hideCreateForm();
    }
    
    public function render()
    {
        return view('livewire.pos.customer-search-modal');
    }
}