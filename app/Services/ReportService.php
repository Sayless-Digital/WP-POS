<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\CashDrawerSession;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate sales summary report
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getSalesSummary(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total');
        $totalTax = $orders->sum('tax_amount');
        $totalDiscounts = $orders->sum('discount_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $totalItems = $orders->sum(function ($order) {
            return $order->items->sum('quantity');
        });

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_tax' => $totalTax,
            'total_discounts' => $totalDiscounts,
            'net_revenue' => $totalRevenue - $totalDiscounts,
            'average_order_value' => $averageOrderValue,
            'total_items_sold' => $totalItems,
            'average_items_per_order' => $totalOrders > 0 ? $totalItems / $totalOrders : 0,
        ];
    }

    /**
     * Get daily sales breakdown
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getDailySales(\DateTime $startDate, \DateTime $endDate): array
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as order_count, SUM(total) as revenue, SUM(tax_amount) as tax, SUM(discount_amount) as discounts')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'order_count' => $item->order_count,
                    'revenue' => $item->revenue,
                    'tax' => $item->tax,
                    'discounts' => $item->discounts,
                    'net_revenue' => $item->revenue - $item->discounts,
                ];
            })
            ->toArray();
    }

    /**
     * Get hourly sales distribution
     *
     * @param \DateTime $date
     * @return array
     */
    public function getHourlySales(\DateTime $date): array
    {
        $sales = Order::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as order_count, SUM(total) as revenue')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Fill in missing hours with zeros
        $hourlySales = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $sale = $sales->firstWhere('hour', $hour);
            $hourlySales[] = [
                'hour' => $hour,
                'time' => sprintf('%02d:00', $hour),
                'order_count' => $sale ? $sale->order_count : 0,
                'revenue' => $sale ? $sale->revenue : 0,
            ];
        }

        return $hourlySales;
    }

    /**
     * Get top selling products
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int $limit
     * @return array
     */
    public function getTopSellingProducts(\DateTime $startDate, \DateTime $endDate, int $limit = 10): array
    {
        return OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed');
        })
            ->selectRaw('product_id, name, SUM(quantity) as total_quantity, SUM(total) as total_revenue, COUNT(DISTINCT order_id) as order_count')
            ->groupBy('product_id', 'name')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'quantity_sold' => $item->total_quantity,
                    'revenue' => $item->total_revenue,
                    'order_count' => $item->order_count,
                    'average_price' => $item->total_quantity > 0 ? $item->total_revenue / $item->total_quantity : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get product performance report
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getProductPerformance(\DateTime $startDate, \DateTime $endDate): array
    {
        $items = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed');
        })
            ->with('product')
            ->get();

        $performance = $items->groupBy('product_id')->map(function ($group) {
            $product = $group->first()->product;
            $totalQuantity = $group->sum('quantity');
            $totalRevenue = $group->sum('total');
            $totalCost = $product && $product->cost_price 
                ? $totalQuantity * $product->cost_price 
                : 0;
            $profit = $totalRevenue - $totalCost;

            return [
                'product_id' => $group->first()->product_id,
                'name' => $group->first()->name,
                'quantity_sold' => $totalQuantity,
                'revenue' => $totalRevenue,
                'cost' => $totalCost,
                'profit' => $profit,
                'profit_margin' => $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0,
                'order_count' => $group->pluck('order_id')->unique()->count(),
            ];
        })->sortByDesc('revenue')->values()->toArray();

        return $performance;
    }

    /**
     * Get cashier performance report
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getCashierPerformance(\DateTime $startDate, \DateTime $endDate): array
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('user_id, COUNT(*) as order_count, SUM(total) as revenue, AVG(total) as average_order_value')
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'cashier_name' => $item->user->name,
                    'order_count' => $item->order_count,
                    'revenue' => $item->revenue,
                    'average_order_value' => $item->average_order_value,
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->toArray();
    }

    /**
     * Get customer analytics
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getCustomerAnalytics(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $totalCustomers = $orders->pluck('customer_id')->filter()->unique()->count();
        $ordersWithCustomers = $orders->where('customer_id', '!=', null)->count();
        $ordersWithoutCustomers = $orders->where('customer_id', null)->count();

        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

        $repeatCustomers = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) as order_count')
            ->groupBy('customer_id')
            ->having('order_count', '>', 1)
            ->count();

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'repeat_customers' => $repeatCustomers,
            'orders_with_customers' => $ordersWithCustomers,
            'orders_without_customers' => $ordersWithoutCustomers,
            'customer_order_rate' => $orders->count() > 0 ? ($ordersWithCustomers / $orders->count()) * 100 : 0,
        ];
    }

    /**
     * Get payment method breakdown
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getPaymentMethodBreakdown(\DateTime $startDate, \DateTime $endDate): array
    {
        return DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'transaction_count' => $item->transaction_count,
                    'total_amount' => $item->total_amount,
                ];
            })
            ->toArray();
    }

    /**
     * Get inventory valuation report
     *
     * @return array
     */
    public function getInventoryValuation(): array
    {
        $products = Product::with('inventory')->where('track_inventory', true)->get();

        $totalValue = 0;
        $totalCost = 0;
        $totalItems = 0;

        $items = $products->map(function ($product) use (&$totalValue, &$totalCost, &$totalItems) {
            $quantity = $product->inventory ? $product->inventory->quantity : 0;
            $value = $quantity * $product->price;
            $cost = $quantity * ($product->cost_price ?? 0);

            $totalValue += $value;
            $totalCost += $cost;
            $totalItems += $quantity;

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'unit_cost' => $product->cost_price,
                'retail_value' => $value,
                'cost_value' => $cost,
                'potential_profit' => $value - $cost,
            ];
        })->sortByDesc('retail_value')->values()->toArray();

        return [
            'summary' => [
                'total_items' => $totalItems,
                'total_retail_value' => $totalValue,
                'total_cost_value' => $totalCost,
                'potential_profit' => $totalValue - $totalCost,
            ],
            'items' => $items,
        ];
    }

    /**
     * Get cash drawer report
     *
     * @param CashDrawerSession $session
     * @return array
     */
    public function getCashDrawerReport(CashDrawerSession $session): array
    {
        $movements = $session->movements()->get();

        $sales = $movements->where('type', 'sale')->sum('amount');
        $refunds = abs($movements->where('type', 'refund')->sum('amount'));
        $withdrawals = abs($movements->where('type', 'withdrawal')->sum('amount'));
        $deposits = $movements->where('type', 'deposit')->sum('amount');

        $expected = $session->opening_balance + $sales - $refunds - $withdrawals + $deposits;
        $actual = $session->closing_balance ?? $expected;
        $difference = $actual - $expected;

        return [
            'session_id' => $session->id,
            'cashier' => $session->user->name,
            'opened_at' => $session->opened_at,
            'closed_at' => $session->closed_at,
            'status' => $session->status,
            'opening_balance' => $session->opening_balance,
            'sales' => $sales,
            'refunds' => $refunds,
            'withdrawals' => $withdrawals,
            'deposits' => $deposits,
            'expected_balance' => $expected,
            'actual_balance' => $actual,
            'difference' => $difference,
            'movements' => $movements->map(function ($movement) {
                return [
                    'type' => $movement->type,
                    'amount' => $movement->amount,
                    'notes' => $movement->notes,
                    'created_at' => $movement->created_at,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get tax report
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getTaxReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $totalSales = $orders->sum('subtotal');
        $totalTax = $orders->sum('tax_amount');
        $totalWithTax = $orders->sum('total');

        // Group by tax rate
        $byTaxRate = $orders->flatMap->items->groupBy('tax_rate')->map(function ($items, $rate) {
            $subtotal = $items->sum(function ($item) {
                return $item->price * $item->quantity;
            });
            $tax = $subtotal * ($rate / 100);

            return [
                'tax_rate' => $rate,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total' => $subtotal + $tax,
            ];
        })->values()->toArray();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_sales' => $totalSales,
            'total_tax' => $totalTax,
            'total_with_tax' => $totalWithTax,
            'by_tax_rate' => $byTaxRate,
        ];
    }

    /**
     * Get profit and loss report
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getProfitLossReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('items.product')
            ->get();

        $revenue = $orders->sum('total');
        $discounts = $orders->sum('discount_amount');
        $grossRevenue = $revenue + $discounts;

        $costOfGoodsSold = 0;
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->product && $item->product->cost_price) {
                    $costOfGoodsSold += $item->product->cost_price * $item->quantity;
                }
            }
        }

        $grossProfit = $revenue - $costOfGoodsSold;
        $grossProfitMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'gross_revenue' => $grossRevenue,
            'discounts' => $discounts,
            'net_revenue' => $revenue,
            'cost_of_goods_sold' => $costOfGoodsSold,
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $grossProfitMargin,
        ];
    }
}