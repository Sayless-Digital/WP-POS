<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class StockAdjustment extends Component
{
    public $inventory;
    public $item;
    
    // Adjustment form
    public $adjustmentType = 'add'; // add, remove, set
    public $quantity = 0;
    public $reason = '';
    public $notes = '';
    
    // Reorder settings
    public $reorderPoint;
    public $reorderQuantity;
    
    // Quick adjustment presets
    public $quickAdjustments = [10, 25, 50, 100];
    
    // Recent adjustments
    public $recentAdjustments = [];
    
    protected $rules = [
        'quantity' => 'required|integer|min:1',
        'reason' => 'required|string|max:255',
        'notes' => 'nullable|string|max:500',
        'reorderPoint' => 'nullable|integer|min:0',
        'reorderQuantity' => 'nullable|integer|min:0',
    ];

    protected $messages = [
        'quantity.required' => 'Please enter a quantity',
        'quantity.min' => 'Quantity must be at least 1',
        'reason.required' => 'Please select or enter a reason',
    ];

    public function mount($inventoryId)
    {
        $this->inventory = Inventory::with('inventoriable')->findOrFail($inventoryId);
        $this->item = $this->inventory->inventoriable;
        
        // Initialize reorder settings
        $this->reorderPoint = $this->inventory->reorder_point;
        $this->reorderQuantity = $this->inventory->reorder_quantity;
        
        // Load recent adjustments
        $this->loadRecentAdjustments();
    }

    public function loadRecentAdjustments()
    {
        $inventoryService = app(InventoryService::class);
        $this->recentAdjustments = $inventoryService->getStockHistory($this->item, 10);
    }

    public function setAdjustmentType($type)
    {
        $this->adjustmentType = $type;
        $this->quantity = 0;
    }

    public function setQuickQuantity($amount)
    {
        $this->quantity = $amount;
    }

    public function adjustStock()
    {
        $this->validate();

        try {
            $inventoryService = app(InventoryService::class);
            
            // Calculate the actual adjustment amount
            $adjustmentAmount = match ($this->adjustmentType) {
                'add' => $this->quantity,
                'remove' => -$this->quantity,
                'set' => $this->quantity - $this->inventory->quantity,
                default => 0,
            };

            // Perform the adjustment
            $inventoryService->adjustStock(
                $this->item,
                $adjustmentAmount,
                'adjustment',
                [
                    'notes' => $this->reason . ($this->notes ? ': ' . $this->notes : ''),
                    'user_id' => Auth::id(),
                ]
            );

            // Refresh inventory
            $this->inventory->refresh();
            
            // Reload recent adjustments
            $this->loadRecentAdjustments();

            // Reset form
            $this->reset(['quantity', 'reason', 'notes']);
            
            session()->flash('success', 'Stock adjusted successfully');
            $this->dispatch('stock-adjusted');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }

    public function updateReorderSettings()
    {
        $this->validate([
            'reorderPoint' => 'required|integer|min:0',
            'reorderQuantity' => 'required|integer|min:0',
        ]);

        try {
            $this->inventory->update([
                'reorder_point' => $this->reorderPoint,
                'reorder_quantity' => $this->reorderQuantity,
            ]);

            session()->flash('success', 'Reorder settings updated successfully');
            $this->dispatch('reorder-settings-updated');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update reorder settings: ' . $e->getMessage());
        }
    }

    public function performStockCount()
    {
        $this->validate([
            'quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $inventoryService = app(InventoryService::class);
            
            $inventoryService->performStockCount(
                $this->item,
                $this->quantity,
                $this->notes ?: 'Physical stock count'
            );

            // Refresh inventory
            $this->inventory->refresh();
            
            // Reload recent adjustments
            $this->loadRecentAdjustments();

            // Reset form
            $this->reset(['quantity', 'notes']);
            
            session()->flash('success', 'Stock count completed successfully');
            $this->dispatch('stock-count-completed');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to perform stock count: ' . $e->getMessage());
        }
    }

    public function getStockStatusProperty()
    {
        if ($this->inventory->quantity <= 0) {
            return ['status' => 'Out of Stock', 'color' => 'red'];
        } elseif ($this->inventory->quantity <= $this->inventory->reorder_point) {
            return ['status' => 'Low Stock', 'color' => 'yellow'];
        }
        return ['status' => 'In Stock', 'color' => 'green'];
    }

    public function getProjectedQuantityProperty()
    {
        return match ($this->adjustmentType) {
            'add' => $this->inventory->quantity + ($this->quantity ?: 0),
            'remove' => max(0, $this->inventory->quantity - ($this->quantity ?: 0)),
            'set' => $this->quantity ?: $this->inventory->quantity,
            default => $this->inventory->quantity,
        };
    }

    public function render()
    {
        return view('livewire.inventory.stock-adjustment', [
            'stockStatus' => $this->stockStatus,
            'projectedQuantity' => $this->projectedQuantity,
        ]);
    }
}