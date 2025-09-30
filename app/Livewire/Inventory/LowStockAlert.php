<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class LowStockAlert extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all'; // all, low_stock, out_of_stock
    public $typeFilter = 'all'; // all, products, variants
    public $sortField = 'quantity';
    public $sortDirection = 'asc';
    public $perPage = 20;

    // Statistics
    public $stats = [
        'low_stock_count' => 0,
        'out_of_stock_count' => 0,
        'total_value_at_risk' => 0,
        'items_need_reorder' => 0,
    ];

    // Bulk actions
    public $selectedItems = [];
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
    ];

    public function mount()
    {
        $this->loadStatistics();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->getAlertsQuery()->pluck('id')->toArray();
        } else {
            $this->selectedItems = [];
        }
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

    public function loadStatistics()
    {
        $inventoryService = app(InventoryService::class);

        // Get all inventory items
        $allInventory = Inventory::with('inventoriable')->get();

        $this->stats['low_stock_count'] = $allInventory->filter(function ($inv) {
            return $inv->quantity > 0 && $inv->quantity <= $inv->reorder_point;
        })->count();

        $this->stats['out_of_stock_count'] = $allInventory->filter(function ($inv) {
            return $inv->quantity <= 0;
        })->count();

        $this->stats['items_need_reorder'] = $allInventory->filter(function ($inv) {
            return $inv->quantity <= $inv->reorder_point;
        })->count();

        // Calculate value at risk (items that are low or out of stock)
        $this->stats['total_value_at_risk'] = $allInventory->filter(function ($inv) {
            return $inv->quantity <= $inv->reorder_point;
        })->sum(function ($inv) {
            $item = $inv->inventoriable;
            return $inv->reorder_quantity * ($item->cost_price ?? $item->price ?? 0);
        });
    }

    public function getAlertsQuery()
    {
        $query = Inventory::with(['inventoriable'])
            ->whereRaw('quantity <= reorder_point');

        // Apply status filter
        switch ($this->statusFilter) {
            case 'low_stock':
                $query->whereRaw('quantity > 0 AND quantity <= reorder_point');
                break;
            case 'out_of_stock':
                $query->where('quantity', '<=', 0);
                break;
        }

        // Apply type filter
        switch ($this->typeFilter) {
            case 'products':
                $query->where('inventoriable_type', Product::class);
                break;
            case 'variants':
                $query->where('inventoriable_type', ProductVariant::class);
                break;
        }

        // Apply search
        if ($this->search) {
            $query->whereHasMorph('inventoriable', [Product::class, ProductVariant::class], function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function markAsResolved($inventoryId)
    {
        try {
            $inventory = Inventory::findOrFail($inventoryId);
            
            // Update reorder point to current quantity to "resolve" the alert
            $inventory->update([
                'reorder_point' => max(0, $inventory->quantity - 1),
            ]);

            $this->loadStatistics();
            session()->flash('success', 'Alert marked as resolved');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to resolve alert: ' . $e->getMessage());
        }
    }

    public function bulkMarkAsResolved()
    {
        if (empty($this->selectedItems)) {
            session()->flash('error', 'No items selected');
            return;
        }

        try {
            foreach ($this->selectedItems as $inventoryId) {
                $inventory = Inventory::find($inventoryId);
                if ($inventory) {
                    $inventory->update([
                        'reorder_point' => max(0, $inventory->quantity - 1),
                    ]);
                }
            }

            $this->selectedItems = [];
            $this->selectAll = false;
            $this->loadStatistics();
            
            session()->flash('success', 'Selected alerts marked as resolved');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to resolve alerts: ' . $e->getMessage());
        }
    }

    public function exportAlerts()
    {
        $alerts = $this->getAlertsQuery()->get();
        
        $exportService = app(\App\Services\ExportService::class);
        
        $data = $alerts->map(function ($inv) {
            $item = $inv->inventoriable;
            $status = $inv->quantity <= 0 ? 'Out of Stock' : 'Low Stock';
            
            return [
                'SKU' => $item->sku ?? 'N/A',
                'Name' => $item->name,
                'Type' => class_basename($inv->inventoriable_type),
                'Current Quantity' => $inv->quantity,
                'Reorder Point' => $inv->reorder_point,
                'Reorder Quantity' => $inv->reorder_quantity,
                'Status' => $status,
                'Shortage' => max(0, $inv->reorder_point - $inv->quantity),
                'Estimated Cost' => number_format($inv->reorder_quantity * ($item->cost_price ?? $item->price ?? 0), 2),
            ];
        })->toArray();

        return $exportService->exportToCsv($data, 'low-stock-alerts-' . now()->format('Y-m-d'));
    }

    public function render()
    {
        $alerts = $this->getAlertsQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.low-stock-alert', [
            'alerts' => $alerts,
        ]);
    }
}