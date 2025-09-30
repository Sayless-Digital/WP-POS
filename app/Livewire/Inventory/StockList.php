<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class StockList extends Component
{
    use WithPagination;

    public $search = '';
    public $stockFilter = 'all'; // all, in_stock, low_stock, out_of_stock
    public $typeFilter = 'all'; // all, products, variants
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 20;
    public $viewMode = 'grid'; // grid, list

    // Statistics
    public $stats = [
        'total_items' => 0,
        'in_stock' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0,
        'total_value' => 0,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'stockFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
        'viewMode' => ['except' => 'grid'],
    ];

    public function mount()
    {
        $this->loadStatistics();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStockFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
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

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function loadStatistics()
    {
        $inventoryService = app(InventoryService::class);

        // Get all inventory records with their items
        $allInventory = Inventory::with('inventoriable')->get();

        $this->stats['total_items'] = $allInventory->count();
        $this->stats['in_stock'] = $allInventory->filter(function ($inv) {
            return $inv->quantity > $inv->reorder_point;
        })->count();
        $this->stats['low_stock'] = $allInventory->filter(function ($inv) {
            return $inv->quantity > 0 && $inv->quantity <= $inv->reorder_point;
        })->count();
        $this->stats['out_of_stock'] = $allInventory->filter(function ($inv) {
            return $inv->quantity <= 0;
        })->count();

        // Calculate total inventory value
        $this->stats['total_value'] = $inventoryService->getTotalInventoryValue();
    }

    public function getInventoryQuery()
    {
        $query = Inventory::with(['inventoriable']);

        // Apply stock filter
        switch ($this->stockFilter) {
            case 'in_stock':
                $query->whereRaw('quantity > reorder_point');
                break;
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

    public function render()
    {
        $inventory = $this->getInventoryQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.stock-list', [
            'inventory' => $inventory,
        ]);
    }

    public function getStockStatusAttribute($item)
    {
        if ($item->quantity <= 0) {
            return 'out_of_stock';
        } elseif ($item->quantity <= $item->reorder_point) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getStockStatusColorAttribute($status)
    {
        return match ($status) {
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
            default => 'gray',
        };
    }

    public function exportInventory()
    {
        $inventory = $this->getInventoryQuery()->get();
        
        $exportService = app(\App\Services\ExportService::class);
        
        $data = $inventory->map(function ($inv) {
            $item = $inv->inventoriable;
            return [
                'SKU' => $item->sku ?? 'N/A',
                'Name' => $item->name,
                'Type' => class_basename($inv->inventoriable_type),
                'Quantity' => $inv->quantity,
                'Reserved' => $inv->reserved_quantity,
                'Available' => $inv->available_quantity,
                'Reorder Point' => $inv->reorder_point,
                'Reorder Quantity' => $inv->reorder_quantity,
                'Status' => $this->getStockStatusAttribute($inv),
                'Value' => number_format($inv->quantity * ($item->cost_price ?? $item->price), 2),
            ];
        })->toArray();

        return $exportService->exportToCsv($data, 'inventory-' . now()->format('Y-m-d'));
    }

    public function refreshStats()
    {
        $this->loadStatistics();
        $this->dispatch('stats-refreshed');
    }
}