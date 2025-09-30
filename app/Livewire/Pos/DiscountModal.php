<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class DiscountModal extends Component
{
    // Modal state
    public bool $show = false;
    
    // Discount data
    public float $discountAmount = 0;
    public string $discountType = 'fixed'; // 'fixed' or 'percentage'
    public string $reason = '';
    
    // Cart data (passed from parent)
    public float $cartSubtotal = 0;
    public float $currentDiscount = 0;
    
    // Validation rules
    protected function rules()
    {
        return [
            'discountAmount' => [
                'required',
                'numeric',
                'min:0',
                $this->discountType === 'percentage' ? 'max:100' : 'max:' . $this->cartSubtotal,
            ],
            'discountType' => 'required|in:fixed,percentage',
            'reason' => 'nullable|string|max:255',
        ];
    }
    
    /**
     * Custom validation messages
     */
    protected function messages()
    {
        return [
            'discountAmount.required' => 'Please enter a discount amount',
            'discountAmount.numeric' => 'Discount must be a number',
            'discountAmount.min' => 'Discount cannot be negative',
            'discountAmount.max' => $this->discountType === 'percentage' 
                ? 'Percentage discount cannot exceed 100%'
                : 'Discount cannot exceed cart subtotal',
        ];
    }
    
    /**
     * Mount component
     */
    public function mount()
    {
        $this->show = false;
        $this->resetForm();
    }
    
    /**
     * Listen for modal open event
     */
    #[On('open-discount-modal')]
    public function openModal(float $cartSubtotal = 0, float $currentDiscount = 0, string $discountType = 'fixed')
    {
        $this->show = true;
        $this->cartSubtotal = $cartSubtotal;
        $this->currentDiscount = $currentDiscount;
        $this->discountAmount = $currentDiscount;
        $this->discountType = $discountType;
        $this->reason = '';
    }
    
    /**
     * Close modal
     */
    public function closeModal()
    {
        $this->show = false;
        $this->resetForm();
    }
    
    /**
     * Reset form
     */
    protected function resetForm()
    {
        $this->discountAmount = 0;
        $this->discountType = 'fixed';
        $this->reason = '';
        $this->cartSubtotal = 0;
        $this->currentDiscount = 0;
        $this->resetValidation();
    }
    
    /**
     * Update discount type
     */
    public function updatedDiscountType()
    {
        // Reset discount amount when type changes
        $this->discountAmount = 0;
        $this->resetValidation();
    }
    
    /**
     * Apply quick discount percentage
     */
    public function applyQuickDiscount(int $percentage)
    {
        $this->discountType = 'percentage';
        $this->discountAmount = $percentage;
    }
    
    /**
     * Apply discount
     */
    public function applyDiscount()
    {
        $this->validate();
        
        try {
            // Dispatch event to parent component
            $this->dispatch('discount-applied', [
                'amount' => $this->discountAmount,
                'type' => $this->discountType,
                'reason' => $this->reason,
            ]);
            
            $this->closeModal();
            $this->dispatch('success', message: 'Discount applied successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to apply discount: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove discount
     */
    public function removeDiscount()
    {
        try {
            $this->dispatch('discount-removed');
            $this->closeModal();
            $this->dispatch('success', message: 'Discount removed');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to remove discount: ' . $e->getMessage());
        }
    }
    
    /**
     * Computed property for calculated discount
     */
    #[Computed]
    public function calculatedDiscount()
    {
        if ($this->discountAmount <= 0) {
            return 0;
        }
        
        if ($this->discountType === 'percentage') {
            return ($this->cartSubtotal * $this->discountAmount) / 100;
        }
        
        return min($this->discountAmount, $this->cartSubtotal);
    }
    
    /**
     * Computed property for new total
     */
    #[Computed]
    public function newTotal()
    {
        return max(0, $this->cartSubtotal - $this->calculatedDiscount);
    }
    
    /**
     * Format currency
     */
    public function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
    
    public function render()
    {
        return view('livewire.pos.discount-modal');
    }
}