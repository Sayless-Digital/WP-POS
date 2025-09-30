<?php

namespace App\Livewire\Reports;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class SalesSummary extends Component
{
    public $period = 'today';
    public $startDate;
    public $endDate;
    public $selectedUser;
    public $paymentMethod = 'all';

    public function mount()
    {
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
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

    public function getSalesStatisticsProperty()
    {
        $query = Order::whereBetween('created_at', [
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        ])->where('status', 'completed');

        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        $orders = $query->get();

        $totalSales = $orders->sum('total');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
        $totalTax = $orders->sum('tax_amount');
        $totalDiscount = $orders->sum('discount_amount');
        $totalSubtotal = $orders->sum('subtotal');

        return [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'total_tax' => $totalTax,
            'total_discount' => $totalDiscount,
            'total_subtotal' => $totalSubtotal,
        ];
    }

    public function getPaymentBreakdownProperty()
    {
        $query = Payment::whereHas('order', function ($q) {
            $q->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])->where('status', 'completed');

            if ($this->selectedUser) {
                $q->where('user_id', $this->selectedUser);
            }
        });

        if ($this->paymentMethod !== 'all') {
            $query->where('payment_method', $this->paymentMethod);
        }

        return $query->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();
    }

    public function getHourlySalesProperty()
    {
        $query = Order::whereBetween('created_at', [
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        ])->where('status', 'completed');

        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        return $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders, SUM(total) as sales')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    public function getTopProductsProperty()
    {
        $query = Order::whereBetween('created_at', [
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        ])->where('status', 'completed');

        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        $orderIds = $query->pluck('id');

        return \DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->selectRaw('name, SUM(quantity) as total_quantity, SUM(total) as total_sales')
            ->groupBy('name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
    }

    public function exportCsv()
    {
        $statistics = $this->salesStatistics;
        $payments = $this->paymentBreakdown;
        $topProducts = $this->topProducts;

        $filename = 'sales_summary_' . $this->startDate . '_to_' . $this->endDate . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($statistics, $payments, $topProducts) {
            $file = fopen('php://output', 'w');

            // Summary section
            fputcsv($file, ['Sales Summary Report']);
            fputcsv($file, ['Period', $this->startDate . ' to ' . $this->endDate]);
            fputcsv($file, []);

            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Sales', number_format($statistics['total_sales'], 2)]);
            fputcsv($file, ['Total Orders', $statistics['total_orders']]);
            fputcsv($file, ['Average Order Value', number_format($statistics['average_order_value'], 2)]);
            fputcsv($file, ['Total Tax', number_format($statistics['total_tax'], 2)]);
            fputcsv($file, ['Total Discount', number_format($statistics['total_discount'], 2)]);
            fputcsv($file, []);

            // Payment breakdown
            fputcsv($file, ['Payment Method Breakdown']);
            fputcsv($file, ['Payment Method', 'Count', 'Total Amount']);
            foreach ($payments as $payment) {
                fputcsv($file, [
                    ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    $payment->count,
                    number_format($payment->total, 2)
                ]);
            }
            fputcsv($file, []);

            // Top products
            fputcsv($file, ['Top 10 Products']);
            fputcsv($file, ['Product Name', 'Quantity Sold', 'Total Sales']);
            foreach ($topProducts as $product) {
                fputcsv($file, [
                    $product->name,
                    $product->total_quantity,
                    number_format($product->total_sales, 2)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.reports.sales-summary', [
            'statistics' => $this->salesStatistics,
            'paymentBreakdown' => $this->paymentBreakdown,
            'hourlySales' => $this->hourlySales,
            'topProducts' => $this->topProducts,
            'users' => User::orderBy('name')->get(),
        ]);
    }
}