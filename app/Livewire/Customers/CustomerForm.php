<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\CustomerService;
use Livewire\Component;

class CustomerForm extends Component
{
    // Customer properties
    public ?Customer $customer = null;
    public $customerId = null;
    public $isEditing = false;
    
    // Form fields
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $city = '';
    public $postal_code = '';
    public $customer_group_id = '';
    public $notes = '';
    public $loyalty_points = 0;
    
    // UI State
    public $activeTab = 'basic';
    public $showModal = true;

    protected $customerService;

    /**
     * Validation rules
     */
    protected function rules()
    {
        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:customers,email' . ($this->isEditing ? ',' . $this->customer->id : ''),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'notes' => 'nullable|string',
        ];

        return $rules;
    }

    /**
     * Custom validation messages
     */
    protected $messages = [
        'first_name.required' => 'First name is required',
        'last_name.required' => 'Last name is required',
        'email.email' => 'Please enter a valid email address',
        'email.unique' => 'This email is already registered',
    ];

    /**
     * Mount the component
     */
    public function mount($customerId = null)
    {
        $this->customerService = app(CustomerService::class);
        $this->customerId = $customerId;
        
        if ($customerId) {
            $this->customer = Customer::with('customerGroup')->find($customerId);
            if ($this->customer) {
                $this->isEditing = true;
                $this->loadCustomer();
            }
        }
    }

    /**
     * Load customer data for editing
     */
    protected function loadCustomer()
    {
        $this->first_name = $this->customer->first_name;
        $this->last_name = $this->customer->last_name;
        $this->email = $this->customer->email;
        $this->phone = $this->customer->phone;
        $this->address = $this->customer->address;
        $this->city = $this->customer->city;
        $this->postal_code = $this->customer->postal_code;
        $this->customer_group_id = $this->customer->customer_group_id;
        $this->notes = $this->customer->notes;
        $this->loyalty_points = $this->customer->loyalty_points;
    }

    /**
     * Switch active tab
     */
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Get full name
     */
    public function getFullNameProperty()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Save customer
     */
    public function save()
    {
        $this->validate();

        try {
            $data = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email ?: null,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'city' => $this->city ?: null,
                'postal_code' => $this->postal_code ?: null,
                'customer_group_id' => $this->customer_group_id ?: null,
                'notes' => $this->notes ?: null,
            ];

            if ($this->isEditing) {
                $customer = $this->customerService->updateCustomer($this->customer, $data);
                $message = 'Customer updated successfully';
                $event = 'customer-updated';
            } else {
                $customer = $this->customerService->createCustomer($data);
                $message = 'Customer created successfully';
                $event = 'customer-created';
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message
            ]);

            $this->dispatch($event);
            $this->closeModal();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error saving customer: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save and add another
     */
    public function saveAndAddAnother()
    {
        $this->validate();

        try {
            $data = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email ?: null,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'city' => $this->city ?: null,
                'postal_code' => $this->postal_code ?: null,
                'customer_group_id' => $this->customer_group_id ?: null,
                'notes' => $this->notes ?: null,
            ];

            $this->customerService->createCustomer($data);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Customer created successfully'
            ]);

            $this->dispatch('customer-created');

            // Reset form for next customer
            $this->reset([
                'first_name', 'last_name', 'email', 'phone',
                'address', 'city', 'postal_code', 'notes'
            ]);
            $this->activeTab = 'basic';

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error saving customer: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Close modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('close-modal');
    }

    /**
     * Cancel and close
     */
    public function cancel()
    {
        $this->closeModal();
    }

    /**
     * Get customer groups for dropdown
     */
    public function getCustomerGroupsProperty()
    {
        return CustomerGroup::orderBy('name')->get();
    }

    /**
     * Get customer statistics (for editing mode)
     */
    public function getStatisticsProperty()
    {
        if (!$this->isEditing || !$this->customer) {
            return null;
        }

        return $this->customerService->getCustomerStatistics($this->customer);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.customers.customer-form', [
            'customerGroups' => $this->customerGroups,
            'statistics' => $this->statistics,
        ]);
    }
}