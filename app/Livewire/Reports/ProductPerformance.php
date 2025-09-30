<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ProductPerformance extends Component
{
    use WithPagination;

    public $period = 'this_month';
    public $startDate;
    public $endDate;
    public $categoryFilter = '';
    public $sortBy = 'revenue';
    public $sortDirection = 'desc';
    public $search = '';

    protected $queryString = ['search', 'categoryFilter'];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedPeriod($value)
    {
        switch ($value) {
            case 'today':
                $this->startDate = Carbon::today()->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = Carbon::yesterday()->format('Y-m-d');
                $this->endDate = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'last_week':
                $this->startDate = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->endDate = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getProductPerformanceProperty()
    {
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->whereBetween('orders.created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->where('orders.status', 'completed')
            ->select(
                'order_items.product_id',
                'order_items.sku',
                'order_items.name as product_name',
                'product_categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_revenue'),
                DB::raw('AVG(order_items.price) as average_price'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('order_items.product_id', 'order_items.sku', 'order_items.name', 'product_categories.name');

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_items.name', 'like', '%' . $this->search . '%')
                  ->orWhere('order_items.sku', 'like', '%' . $this->search . '%');
            });
        }

        // Category filter
        if ($this->categoryFilter) {
            $query->where('products.category_id', $this->categoryFilter);
        }

        // Sorting
        switch ($this->sortBy) {
            case 'revenue':
                $query->orderBy('total_revenue', $this->sortDirection);
                break;
            case 'quantity':
                $query->orderBy('total_quantity', $this->sortDirection);
                break;
            case 'orders':
                $query->orderBy('order_count', $this->sortDirection);
                break;
            case 'name':
                $query->orderBy('order_items.name', $this->sortDirection);
                break;
            default:
                $query->orderBy('total_revenue', 'desc');
        }

        return $query->paginate(20);
    }

    public function getPerformanceStatisticsProperty()
    {
        $products = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->where('orders.status', 'completed')
            ->select(
                DB::raw('COUNT(DISTINCT order_items.product_id) as unique_products'),
                DB::raw('SUM(order_items.quantity) as total_items_sold'),
                DB::raw('SUM(order_items.total) as total_revenue'),
                DB::raw('AVG(order_items.price) as average_item_price')
            )
            ->first();

        return [
            'unique_products' => $products->unique_products ?? 0,
            'total_items_sold' => $products->total_items_sold ?? 0,
            'total_revenue' => $products->total_revenue ?? 0,
            'average_item_price' => $products->average_item_price ?? 0,
        ];
    }

    public function getTopCategoriesProperty()
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->whereBetween('orders.created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->where('orders.status', 'completed')
            ->select(
                'product_categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )
            ->groupBy('product_categories.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();
    }

    public function exportCsv()
    {
        $products = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->whereBetween('orders.created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->where('orders.status', 'completed')
            ->select(
                'order_items.sku',
                'order_items.name as product_name',
                'product_categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_revenue'),
                DB::raw('AVG(order_items.price) as average_price'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('order_items.sku', 'order_items.name', 'product_categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        $filename = 'product_performance_' . $this->startDate . '_to_' . $this->endDate . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['Product Performance Report']);
            fputcsv($file, ['Period', $this->startDate . ' to ' . $this->endDate]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['SKU', 'Product Name', 'Category', 'Quantity Sold', 'Total Revenue', 'Average Price', 'Orders']);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product->sku,
                    $product->product_name,
                    $product->category_name ?? 'N/A',
                    $product->total_quantity,
                    number_format($product->total_revenue, 2),
                    number_format($product->average_price, 2),
                    $product->order_count,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.reports.product-performance', [
            'productPerformance' => $this->productPerformance,
            'statistics' => $this->performanceStatistics,
            'topCategories' => $this->topCategories,
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }
}