<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\OrderService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class OrderReports extends Component
{
    public $reportType = 'overview'; // overview, sales, products, payments
    public $startDate;
    public $endDate;
    public $period = 'today'; // today, week, month, year, custom
    
    // Statistics
    public $statistics = [];
    public $salesData = [];
    public $topProducts = [];
    public $paymentBreakdown = [];
    public $hourlyData = [];
    public $statusBreakdown = [];

    public function mount()
    {
        $this->setPeriod('today');
    }

    public function updatedPeriod()
    {
        $this->setPeriod($this->period);
    }

    public function updatedStartDate()
    {
        if ($this->period === 'custom') {
            $this->loadReportData();
        }
    }

    public function updatedEndDate()
    {
        if ($this->period === 'custom') {
            $this->loadReportData();
        }
    }

    public function setPeriod($period)
    {
        $this->period = $period;

        switch ($period) {
            case 'today':
                $this->startDate = now()->startOfDay()->format('Y-m-d H:i:s');
                $this->endDate = now()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d H:i:s');
                $this->endDate = now()->endOfWeek()->format('Y-m-d H:i:s');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d H:i:s');
                $this->endDate = now()->endOfMonth()->format('Y-m-d H:i:s');
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->format('Y-m-d H:i:s');
                $this->endDate = now()->endOfYear()->format('Y-m-d H:i:s');
                break;
        }

        $this->loadReportData();
    }

    public function loadReportData()
    {
        $orderService = app(OrderService::class);
        
        $startDateTime = new \DateTime($this->startDate);
        $endDateTime = new \DateTime($this->endDate);

        // Load statistics
        $this->statistics = $orderService->getOrderStatistics($startDateTime, $endDateTime);
        
        // Load top products
        $this->topProducts = $orderService->getTopSellingProducts($startDateTime, $endDateTime, 10);
        
        // Load payment breakdown
        $this->loadPaymentBreakdown();
        
        // Load status breakdown
        $this->statusBreakdown = $orderService->getOrderTotalsByStatus($startDateTime, $endDateTime);
        
        // Load hourly data for today
        if ($this->period === 'today') {
            $this->hourlyData = $orderService->getHourlySales($startDateTime);
        }
        
        // Load daily sales data
        $this->loadSalesData();
    }

    public function loadPaymentBreakdown()
    {
        $this->paymentBreakdown = Payment::whereHas('order', function ($query) {
                $query->whereBetween('created_at', [$this->startDate, $this->endDate])
                    ->where('status', 'completed');
            })
            ->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->method,
                    'method_name' => $this->getPaymentMethodName($item->method),
                    'count' => $item->count,
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    public function loadSalesData()
    {
        $orders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(discount_amount) as total_discounts')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->salesData = $orders->map(function ($item) {
            return [
                'date' => $item->date,
                'order_count' => $item->order_count,
                'total_sales' => $item->total_sales,
                'total_tax' => $item->total_tax,
                'total_discounts' => $item->total_discounts,
                'average_order' => $item->order_count > 0 ? $item->total_sales / $item->order_count : 0,
            ];
        })->toArray();
    }

    public function getPaymentMethodName($method)
    {
        return match($method) {
            'cash' => 'Cash',
            'card' => 'Card',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Bank Transfer',
            'other' => 'Other',
            default => ucfirst($method),
        };
    }

    public function exportReport()
    {
        $csv = "WP-POS Sales Report\n";
        $csv .= "Period: {$this->startDate} to {$this->endDate}\n\n";
        
        $csv .= "SUMMARY\n";
        $csv .= "Total Orders,{$this->statistics['total_orders']}\n";
        $csv .= "Total Revenue,\${$this->statistics['total_revenue']}\n";
        $csv .= "Average Order Value,\${$this->statistics['average_order_value']}\n";
        $csv .= "Total Items Sold,{$this->statistics['total_items_sold']}\n";
        $csv .= "Total Tax,\${$this->statistics['total_tax']}\n";
        $csv .= "Total Discounts,\${$this->statistics['total_discounts']}\n\n";
        
        $csv .= "TOP PRODUCTS\n";
        $csv .= "Product,Quantity Sold,Revenue,Orders\n";
        foreach ($this->topProducts as $product) {
            $csv .= "\"{$product->product->name}\",{$product->total_quantity},\${$product->total_revenue},{$product->order_count}\n";
        }
        
        $csv .= "\nPAYMENT METHODS\n";
        $csv .= "Method,Count,Total\n";
        foreach ($this->paymentBreakdown as $payment) {
            $csv .= "{$payment['method_name']},{$payment['count']},\${$payment['total']}\n";
        }
        
        $csv .= "\nDAILY SALES\n";
        $csv .= "Date,Orders,Sales,Tax,Discounts,Average Order\n";
        foreach ($this->salesData as $day) {
            $csv .= "{$day['date']},{$day['order_count']},\${$day['total_sales']},\${$day['total_tax']},\${$day['total_discounts']},\${$day['average_order']}\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'sales-report-' . now()->format('Y-m-d') . '.csv');
    }

    public function render()
    {
        return view('livewire.orders.order-reports');
    }
}