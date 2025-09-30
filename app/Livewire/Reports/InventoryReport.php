<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryReport extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $stockStatus = 'all';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $startDate;
    public $endDate;

    protected $queryString = ['search', 'categoryFilter', 'stockStatus'];

    public function mount()
    {
        $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingStockStatus()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getInventoryStatisticsProperty()
    {
        $totalProducts = Product::where('is_active', true)->count();
        $totalVariants = ProductVariant::where('is_active', true)->count();
        
        $totalValue = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;

        // Calculate for products
        $products = Product::with('inventory')->where('is_active', true)->get();
        foreach ($products as $product) {
            if ($product->inventory) {
                $totalValue += $product->inventory->quantity * ($product->cost_price ?? $product->price);
                
                if ($product->inventory->quantity <= 0) {
                    $outOfStockCount++;
                } elseif ($product->inventory->quantity <= $product->inventory->low_stock_threshold) {
                    $lowStockCount++;
                }
            }
        }

        // Calculate for variants
        $variants = ProductVariant::with('inventory')->where('is_active', true)->get();
        foreach ($variants as $variant) {
            if ($variant->inventory) {
                $totalValue += $variant->inventory->quantity * ($variant->cost_price ?? $variant->price);
                
                if ($variant->inventory->quantity <= 0) {
                    $outOfStockCount++;
                } elseif ($variant->inventory->quantity <= $variant->inventory->low_stock_threshold) {
                    $lowStockCount++;
                }
            }
        }

        return [
            'total_items' => $totalProducts + $totalVariants,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];
    }

    public function getStockMovementsSummaryProperty()
    {
        $movements = StockMovement::whereBetween('created_at', [
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        ])->get();

        $summary = [
            'sale' => ['count' => 0, 'quantity' => 0],
            'purchase' => ['count' => 0, 'quantity' => 0],
            'adjustment' => ['count' => 0, 'quantity' => 0],
            'return' => ['count' => 0, 'quantity' => 0],
        ];

        foreach ($movements as $movement) {
            if (isset($summary[$movement->type])) {
                $summary[$movement->type]['count']++;
                $summary[$movement->type]['quantity'] += abs($movement->quantity);
            }
        }

        return $summary;
    }

    public function getProductsProperty()
    {
        $query = Product::with(['inventory', 'category'])
            ->where('is_active', true);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        // Category filter
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        // Stock status filter
        if ($this->stockStatus !== 'all') {
            $query->whereHas('inventory', function ($q) {
                if ($this->stockStatus === 'low') {
                    $q->whereRaw('quantity <= low_stock_threshold AND quantity > 0');
                } elseif ($this->stockStatus === 'out') {
                    $q->where('quantity', '<=', 0);
                } elseif ($this->stockStatus === 'in_stock') {
                    $q->whereRaw('quantity > low_stock_threshold');
                }
            });
        }

        // Sorting
        if ($this->sortBy === 'stock') {
            $query->leftJoin('inventory', function ($join) {
                $join->on('products.id', '=', 'inventory.inventoriable_id')
                     ->where('inventory.inventoriable_type', '=', 'App\\Models\\Product');
            })->orderBy('inventory.quantity', $this->sortDirection)
              ->select('products.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate(20);
    }

    public function exportCsv()
    {
        $products = Product::with(['inventory', 'category'])
            ->where('is_active', true)
            ->get();

        $filename = 'inventory_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['Inventory Report - ' . date('Y-m-d H:i:s')]);
            fputcsv($file, []);
            fputcsv($file, ['SKU', 'Product Name', 'Category', 'Stock Quantity', 'Low Stock Threshold', 'Status', 'Unit Price', 'Total Value']);

            foreach ($products as $product) {
                $inventory = $product->inventory;
                $quantity = $inventory ? $inventory->quantity : 0;
                $threshold = $inventory ? $inventory->low_stock_threshold : 0;
                
                $status = 'In Stock';
                if ($quantity <= 0) {
                    $status = 'Out of Stock';
                } elseif ($quantity <= $threshold) {
                    $status = 'Low Stock';
                }

                $totalValue = $quantity * ($product->cost_price ?? $product->price);

                fputcsv($file, [
                    $product->sku,
                    $product->name,
                    $product->category ? $product->category->name : 'N/A',
                    $quantity,
                    $threshold,
                    $status,
                    number_format($product->price, 2),
                    number_format($totalValue, 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.reports.inventory-report', [
            'statistics' => $this->inventoryStatistics,
            'movementsSummary' => $this->stockMovementsSummary,
            'products' => $this->products,
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }
}