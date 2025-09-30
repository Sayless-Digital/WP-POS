<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class StockMovements extends Component
{
    use WithPagination;

    public $inventory;
    public $item;
    
    // Filters
    public $search = '';
    public $typeFilter = 'all'; // all, in, out
    public $reasonFilter = 'all';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 20;
    
    // Statistics
    public $stats = [
        'total_movements' => 0,
        'stock_in' => 0,
        'stock_out' => 0,
        'net_change' => 0,
    ];
    
    // Available reasons for filtering
    public $availableReasons = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
        'reasonFilter' => ['except' => 'all'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount($inventoryId)
    {
        $this->inventory = Inventory::with('inventoriable')->findOrFail($inventoryId);
        $this->item = $this->inventory->inventoriable;
        
        // Set default date range (last 30 days)
        $this->dateTo = now()->format('Y-m-d');
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        
        // Load available reasons
        $this->loadAvailableReasons();
        
        // Load statistics
        $this->loadStatistics();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingReasonFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
        $this->loadStatistics();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
        $this->loadStatistics();
    }

    public function loadAvailableReasons()
    {
        $this->availableReasons = StockMovement::where('inventory_id', $this->inventory->id)
            ->distinct()
            ->pluck('reason')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    public function loadStatistics()
    {
        $query = StockMovement::where('inventory_id', $this->inventory->id);
        
        // Apply date filter
        if ($this->dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }
        if ($this->dateTo) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }
        
        $movements = $query->get();
        
        $this->stats['total_movements'] = $movements->count();
        $this->stats['stock_in'] = $movements->where('type', 'in')->sum('quantity');
        $this->stats['stock_out'] = $movements->where('type', 'out')->sum('quantity');
        $this->stats['net_change'] = $this->stats['stock_in'] - $this->stats['stock_out'];
    }

    public function getMovementsQuery()
    {
        $query = StockMovement::where('inventory_id', $this->inventory->id)
            ->with('user');
        
        // Apply type filter
        if ($this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }
        
        // Apply reason filter
        if ($this->reasonFilter !== 'all') {
            $query->where('reason', $this->reasonFilter);
        }
        
        // Apply date range filter
        if ($this->dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }
        if ($this->dateTo) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }
        
        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhere('notes', 'like', '%' . $this->search . '%');
            });
        }
        
        return $query;
    }

    public function resetFilters()
    {
        $this->reset(['search', 'typeFilter', 'reasonFilter']);
        $this->dateTo = now()->format('Y-m-d');
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->loadStatistics();
        $this->resetPage();
    }

    public function exportMovements()
    {
        $movements = $this->getMovementsQuery()->get();
        
        $exportService = app(\App\Services\ExportService::class);
        
        $data = $movements->map(function ($movement) {
            return [
                'Date' => $movement->created_at->format('Y-m-d H:i:s'),
                'Type' => ucfirst($movement->type),
                'Quantity' => $movement->quantity,
                'Old Quantity' => $movement->old_quantity,
                'New Quantity' => $movement->new_quantity,
                'Change' => $movement->quantity_difference,
                'Reason' => $movement->reason,
                'Notes' => $movement->notes ?? '',
                'User' => $movement->user->name ?? 'System',
            ];
        })->toArray();

        return $exportService->exportToCsv($data, 'stock-movements-' . now()->format('Y-m-d'));
    }

    public function render()
    {
        $movements = $this->getMovementsQuery()
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.inventory.stock-movements', [
            'movements' => $movements,
        ]);
    }
}