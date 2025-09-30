<?php

namespace App\Livewire\Pos;

use App\Models\HeldOrder;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class HeldOrdersModal extends Component
{
    // Modal state
    public bool $show = false;
    
    // Search and filter
    public string $searchQuery = '';
    public string $filterBy = 'all'; // 'all', 'mine', 'today'
    
    // Selected order for preview
    public ?int $selectedOrderId = null;
    
    /**
     * Mount component
     */
    public function mount()
    {
        $this->show = false;
        $this->searchQuery = '';
        $this->filterBy = 'all';
    }
    
    /**
     * Listen for modal open event
     */
    #[On('open-held-orders-modal')]
    public function openModal()
    {
        $this->show = true;
        $this->searchQuery = '';
        $this->selectedOrderId = null;
    }
    
    /**
     * Close modal
     */
    public function closeModal()
    {
        $this->show = false;
        $this->searchQuery = '';
        $this->selectedOrderId = null;
    }
    
    /**
     * Search held orders
     */
    public function updatedSearchQuery()
    {
        $this->selectedOrderId = null;
    }
    
    /**
     * Update filter
     */
    public function updatedFilterBy()
    {
        $this->selectedOrderId = null;
    }
    
    /**
     * Select order for preview
     */
    public function selectOrder(int $orderId)
    {
        $this->selectedOrderId = $orderId;
    }
    
    /**
     * Resume held order
     */
    public function resumeOrder(int $orderId)
    {
        try {
            $heldOrder = HeldOrder::findOrFail($orderId);
            
            // Dispatch event to POS terminal to resume order
            $this->dispatch('resume-held-order', heldOrderId: $orderId);
            
            $this->closeModal();
            $this->dispatch('success', message: 'Order resumed successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to resume order: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete held order
     */
    public function deleteOrder(int $orderId)
    {
        try {
            $heldOrder = HeldOrder::findOrFail($orderId);
            
            // Check if user owns this order or is admin
            if ($heldOrder->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
                $this->dispatch('error', message: 'You do not have permission to delete this order');
                return;
            }
            
            $heldOrder->delete();
            
            // Reset selection if deleted order was selected
            if ($this->selectedOrderId === $orderId) {
                $this->selectedOrderId = null;
            }
            
            $this->dispatch('success', message: 'Held order deleted successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to delete order: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete all held orders (admin only)
     */
    public function deleteAllOrders()
    {
        try {
            if (!auth()->user()->hasRole('admin')) {
                $this->dispatch('error', message: 'Only administrators can delete all orders');
                return;
            }
            
            HeldOrder::query()->delete();
            $this->selectedOrderId = null;
            
            $this->dispatch('success', message: 'All held orders deleted successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to delete orders: ' . $e->getMessage());
        }
    }
    
    /**
     * Computed property for held orders list
     */
    #[Computed]
    public function heldOrders()
    {
        $query = HeldOrder::with(['user', 'customer'])
            ->orderBy('created_at', 'desc');
        
        // Apply filter
        switch ($this->filterBy) {
            case 'mine':
                $query->where('user_id', auth()->id());
                break;
            case 'today':
                $query->whereDate('created_at', today());
                break;
        }
        
        // Apply search
        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('reference', 'like', "%{$this->searchQuery}%")
                  ->orWhere('notes', 'like', "%{$this->searchQuery}%")
                  ->orWhereHas('customer', function ($customerQuery) {
                      $customerQuery->where('name', 'like', "%{$this->searchQuery}%");
                  });
            });
        }
        
        return $query->get();
    }
    
    /**
     * Computed property for selected order details
     */
    #[Computed]
    public function selectedOrder()
    {
        if (!$this->selectedOrderId) {
            return null;
        }
        
        return HeldOrder::with(['user', 'customer'])->find($this->selectedOrderId);
    }
    
    /**
     * Get order items count
     */
    public function getOrderItemsCount(array $items): int
    {
        return collect($items)->sum('quantity');
    }
    
    /**
     * Format currency
     */
    public function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
    
    /**
     * Get time ago
     */
    public function getTimeAgo($date): string
    {
        return \Carbon\Carbon::parse($date)->diffForHumans();
    }
    
    public function render()
    {
        return view('livewire.pos.held-orders-modal');
    }
}