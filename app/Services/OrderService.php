<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\SyncQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get order by ID with relationships
     *
     * @param int $orderId
     * @return Order
     */
    public function getOrder(int $orderId): Order
    {
        return Order::with(['items', 'payments', 'customer', 'user', 'refunds'])
            ->findOrFail($orderId);
    }

    /**
     * Get orders with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOrders(array $filters = [], int $perPage = 20)
    {
        $query = Order::with(['customer', 'user', 'items']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment status
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by customer
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by user/cashier
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Filter by order number
        if (!empty($filters['order_number'])) {
            $query->where('order_number', 'like', "%{$filters['order_number']}%");
        }

        // Filter by sync status
        if (isset($filters['is_synced'])) {
            $query->where('is_synced', $filters['is_synced']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Search orders
     *
     * @param string $query
     * @return Collection
     */
    public function searchOrders(string $query): Collection
    {
        return Order::where('order_number', 'like', "%{$query}%")
            ->orWhereHas('customer', function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->with(['customer', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Update order status
     *
     * @param Order $order
     * @param string $status
     * @return Order
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        // Queue for sync if status changed
        SyncQueue::create([
            'syncable_type' => Order::class,
            'syncable_id' => $order->id,
            'action' => 'update',
            'status' => 'pending',
        ]);

        return $order->fresh();
    }

    /**
     * Cancel an order
     *
     * @param Order $order
     * @param string|null $reason
     * @return Order
     * @throws \Exception
     */
    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        if ($order->status === 'cancelled') {
            throw new \Exception("Order is already cancelled");
        }

        if ($order->status === 'refunded') {
            throw new \Exception("Cannot cancel a refunded order");
        }

        DB::beginTransaction();

        try {
            // Restore inventory for completed orders
            if ($order->status === 'completed') {
                foreach ($order->items as $item) {
                    if ($item->product && $item->product->track_inventory) {
                        $this->inventoryService->processReturn(
                            $item->variant ?? $item->product,
                            $item->quantity,
                            [
                                'reference_type' => Order::class,
                                'reference_id' => $order->id,
                                'notes' => "Order cancelled - {$reason}",
                            ]
                        );
                    }
                }
            }

            // Update order status
            $order->update([
                'status' => 'cancelled',
                'notes' => $order->notes . "\nCancelled: " . ($reason ?? 'No reason provided'),
            ]);

            // Queue for sync
            SyncQueue::create([
                'syncable_type' => Order::class,
                'syncable_id' => $order->id,
                'action' => 'update',
                'status' => 'pending',
            ]);

            DB::commit();

            return $order->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get order statistics for a date range
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getOrderStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total'),
            'average_order_value' => $orders->avg('total'),
            'total_items_sold' => $orders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
            'total_tax' => $orders->sum('tax_amount'),
            'total_discounts' => $orders->sum('discount_amount'),
        ];
    }

    /**
     * Get top selling products
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int $limit
     * @return Collection
     */
    public function getTopSellingProducts(\DateTime $startDate, \DateTime $endDate, int $limit = 10): Collection
    {
        return OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed');
        })
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total) as total_revenue, COUNT(*) as order_count')
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->with('product')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customer order history
     *
     * @param Customer $customer
     * @param int $limit
     * @return Collection
     */
    public function getCustomerOrderHistory(Customer $customer, int $limit = 20): Collection
    {
        return Order::where('customer_id', $customer->id)
            ->with(['items', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get daily sales summary
     *
     * @param \DateTime $date
     * @return array
     */
    public function getDailySalesSummary(\DateTime $date): array
    {
        $orders = Order::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->with('payments')
            ->get();

        $paymentsByMethod = $orders->flatMap->payments->groupBy('payment_method');

        return [
            'date' => $date->format('Y-m-d'),
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total'),
            'total_tax' => $orders->sum('tax_amount'),
            'total_discounts' => $orders->sum('discount_amount'),
            'payments_by_method' => $paymentsByMethod->map(function ($payments) {
                return [
                    'count' => $payments->count(),
                    'total' => $payments->sum('amount'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get orders pending sync
     *
     * @return Collection
     */
    public function getOrdersPendingSync(): Collection
    {
        return Order::where('is_synced', false)
            ->where('status', 'completed')
            ->with(['items', 'payments', 'customer'])
            ->get();
    }

    /**
     * Mark order as synced
     *
     * @param Order $order
     * @param int|null $woocommerceId
     * @return Order
     */
    public function markAsSynced(Order $order, ?int $woocommerceId = null): Order
    {
        $order->update([
            'is_synced' => true,
            'synced_at' => now(),
            'woocommerce_id' => $woocommerceId,
        ]);

        return $order;
    }

    /**
     * Get order totals by status
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getOrderTotalsByStatus(\DateTime $startDate, \DateTime $endDate): array
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            })
            ->toArray();
    }

    /**
     * Get hourly sales data
     *
     * @param \DateTime $date
     * @return array
     */
    public function getHourlySales(\DateTime $date): array
    {
        return Order::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as order_count, SUM(total) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->hour => [
                    'order_count' => $item->order_count,
                    'total' => $item->total,
                ]];
            })
            ->toArray();
    }

    /**
     * Calculate order profitability
     *
     * @param Order $order
     * @return array
     */
    public function calculateProfitability(Order $order): array
    {
        $totalCost = 0;
        $totalRevenue = $order->total;

        foreach ($order->items as $item) {
            $product = $item->variant ?? $item->product;
            if ($product && $product->cost_price) {
                $totalCost += $product->cost_price * $item->quantity;
            }
        }

        $profit = $totalRevenue - $totalCost;
        $profitMargin = $totalCost > 0 ? ($profit / $totalRevenue) * 100 : 0;

        return [
            'revenue' => $totalRevenue,
            'cost' => $totalCost,
            'profit' => $profit,
            'profit_margin' => $profitMargin,
        ];
    }

    /**
     * Duplicate an order (for reordering)
     *
     * @param Order $order
     * @return array Cart data
     */
    public function duplicateOrderToCart(Order $order): array
    {
        $cart = [];

        foreach ($order->items as $item) {
            $product = $item->variant ?? $item->product;
            
            if ($product && $product->is_active) {
                $cart[] = [
                    'type' => $item->variant_id ? 'variant' : 'product',
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'price' => $product->price, // Use current price
                    'quantity' => $item->quantity,
                    'tax_rate' => $product->tax_rate ?? 0,
                    'discount_amount' => 0,
                    'track_inventory' => $product->track_inventory ?? true,
                    'subtotal' => $product->price * $item->quantity,
                ];
            }
        }

        return $cart;
    }
}