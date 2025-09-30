<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;

    // Search and filters
    public $search = '';
    public $categoryFilter = '';
    public $typeFilter = '';
    public $stockFilter = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    
    // View options
    public $viewMode = 'grid'; // 'grid' or 'list'
    public $perPage = 12;
    
    // Bulk actions
    public $selectedProducts = [];
    public $selectAll = false;
    
    // Modals
    public $showFilters = false;
    public $showBulkActions = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'stockFilter' => ['except' => ''],
        'viewMode' => ['except' => 'grid'],
    ];

    /**
     * Reset pagination when search or filters change
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingStockFilter()
    {
        $this->resetPage();
    }

    /**
     * Toggle view mode between grid and list
     */
    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    /**
     * Sort products by column
     */
    public function sortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->reset(['search', 'categoryFilter', 'typeFilter', 'stockFilter']);
        $this->resetPage();
    }

    /**
     * Toggle product selection
     */
    public function toggleProductSelection($productId)
    {
        if (in_array($productId, $this->selectedProducts)) {
            $this->selectedProducts = array_diff($this->selectedProducts, [$productId]);
        } else {
            $this->selectedProducts[] = $productId;
        }
    }

    /**
     * Select all products on current page
     */
    public function selectAllOnPage()
    {
        $productIds = $this->getProductsQuery()->pluck('id')->toArray();
        $this->selectedProducts = array_unique(array_merge($this->selectedProducts, $productIds));
        $this->selectAll = true;
    }

    /**
     * Deselect all products
     */
    public function deselectAll()
    {
        $this->selectedProducts = [];
        $this->selectAll = false;
    }

    /**
     * Bulk activate products
     */
    public function bulkActivate()
    {
        if (empty($this->selectedProducts)) {
            return;
        }

        Product::whereIn('id', $this->selectedProducts)->update(['is_active' => true]);
        
        $this->dispatch('product-updated');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedProducts) . ' products activated successfully'
        ]);
        
        $this->deselectAll();
    }

    /**
     * Bulk deactivate products
     */
    public function bulkDeactivate()
    {
        if (empty($this->selectedProducts)) {
            return;
        }

        Product::whereIn('id', $this->selectedProducts)->update(['is_active' => false]);
        
        $this->dispatch('product-updated');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedProducts) . ' products deactivated successfully'
        ]);
        
        $this->deselectAll();
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete()
    {
        if (empty($this->selectedProducts)) {
            return;
        }

        // Check if any products have order history
        $productsWithOrders = Product::whereIn('id', $this->selectedProducts)
            ->whereHas('orderItems')
            ->count();

        if ($productsWithOrders > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete products with order history. Deactivate them instead.'
            ]);
            return;
        }

        Product::whereIn('id', $this->selectedProducts)->delete();
        
        $this->dispatch('product-updated');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedProducts) . ' products deleted successfully'
        ]);
        
        $this->deselectAll();
    }

    /**
     * Export selected products
     */
    public function exportSelected()
    {
        if (empty($this->selectedProducts)) {
            return;
        }

        // Dispatch event to handle export
        $this->dispatch('export-products', ['productIds' => $this->selectedProducts]);
    }

    /**
     * Get products query with filters
     */
    protected function getProductsQuery()
    {
        $query = Product::query()
            ->with(['category', 'inventory', 'variants']);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Apply category filter
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        // Apply type filter
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        // Apply stock filter
        if ($this->stockFilter === 'in_stock') {
            $query->whereHas('inventory', function ($q) {
                $q->whereRaw('quantity > reserved_quantity');
            });
        } elseif ($this->stockFilter === 'low_stock') {
            $query->whereHas('inventory', function ($q) {
                $q->whereRaw('quantity <= low_stock_threshold');
            });
        } elseif ($this->stockFilter === 'out_of_stock') {
            $query->whereHas('inventory', function ($q) {
                $q->whereRaw('quantity <= reserved_quantity');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query;
    }

    /**
     * Get categories for filter dropdown
     */
    public function getCategoriesProperty()
    {
        return ProductCategory::orderBy('name')->get();
    }

    /**
     * Get filter counts
     */
    public function getFilterCountsProperty()
    {
        return [
            'all' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'low_stock' => Product::whereHas('inventory', function ($q) {
                $q->whereRaw('quantity <= low_stock_threshold');
            })->count(),
        ];
    }

    /**
     * Render the component
     */
    public function render()
    {
        $products = $this->getProductsQuery()->paginate($this->perPage);

        return view('livewire.products.product-list', [
            'products' => $products,
            'categories' => $this->categories,
            'filterCounts' => $this->filterCounts,
        ]);
    }
}