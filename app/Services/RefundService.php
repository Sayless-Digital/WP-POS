<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CashDrawerSession;
use App\Models\CashMovement;
use App\Models\SyncQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RefundService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process a full order refund
     *
     * @param Order $order
     * @param string $reason
     * @param string $method
     * @param array $options
     * @return Refund
     * @throws \Exception
     */
    public function processFullRefund(
        Order $order,
        string $reason,
        string $method = 'cash',
        array $options = []
    ): Refund {
        if ($order->status === 'refunded') {
            throw new \Exception("Order has already been refunded");
        }

        if ($order->status === 'cancelled') {
            throw new \Exception("Cannot refund a cancelled order");
        }

        return $this->processRefund($order, $order->total, $reason, $method, $options);
    }

    /**
     * Process a partial refund
     *
     * @param Order $order
     * @param float $amount
     * @param string $reason
     * @param string $method
     * @param array $options
     * @return Refund
     * @throws \Exception
     */
    public function processPartialRefund(
        Order $order,
        float $amount,
        string $reason,
        string $method = 'cash',
        array $options = []
    ): Refund {
        if ($amount <= 0) {
            throw new \Exception("Refund amount must be greater than zero");
        }

        if ($amount > $order->total) {
            throw new \Exception("Refund amount cannot exceed order total");
        }

        $totalRefunded = $order->refunds()->sum('amount');
        if ($totalRefunded + $amount > $order->total) {
            throw new \Exception("Total refunds would exceed order total");
        }

        return $this->processRefund($order, $amount, $reason, $method, $options);
    }

    /**
     * Process refund
     *
     * @param Order $order
     * @param float $amount
     * @param string $reason
     * @param string $method
     * @param array $options
     * @return Refund
     * @throws \Exception
     */
    protected function processRefund(
        Order $order,
        float $amount,
        string $reason,
        string $method,
        array $options = []
    ): Refund {
        DB::beginTransaction();

        try {
            // Validate refund method
            if (!$this->isValidRefundMethod($method)) {
                throw new \Exception("Invalid refund method: {$method}");
            }

            // Create refund record
            $refund = Refund::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'amount' => $amount,
                'reason' => $reason,
                'refund_method' => $method,
            ]);

            // Restore inventory if full refund or items specified
            if ($amount >= $order->total || !empty($options['items'])) {
                $this->restoreInventory($order, $options['items'] ?? null);
            }

            // Update order status
            $totalRefunded = $order->refunds()->sum('amount');
            if ($totalRefunded >= $order->total) {
                $order->update([
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                ]);
            }

            // Record cash drawer movement if cash refund
            if ($method === 'cash') {
                $this->recordCashMovement($order, $amount);
            }

            // Queue for sync
            SyncQueue::create([
                'syncable_type' => Order::class,
                'syncable_id' => $order->id,
                'action' => 'update',
                'status' => 'pending',
            ]);

            DB::commit();

            return $refund->fresh(['order', 'user']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process item-specific refund
     *
     * @param Order $order
     * @param array $items Array of ['item_id' => int, 'quantity' => int]
     * @param string $reason
     * @param string $method
     * @return Refund
     * @throws \Exception
     */
    public function processItemRefund(
        Order $order,
        array $items,
        string $reason,
        string $method = 'cash'
    ): Refund {
        DB::beginTransaction();

        try {
            $refundAmount = 0;

            // Calculate refund amount and validate items
            foreach ($items as $itemData) {
                $orderItem = OrderItem::where('order_id', $order->id)
                    ->where('id', $itemData['item_id'])
                    ->first();

                if (!$orderItem) {
                    throw new \Exception("Order item not found");
                }

                if ($itemData['quantity'] > $orderItem->quantity) {
                    throw new \Exception("Refund quantity exceeds order quantity");
                }

                // Calculate proportional refund
                $itemRefund = ($orderItem->total / $orderItem->quantity) * $itemData['quantity'];
                $refundAmount += $itemRefund;
            }

            // Process the refund
            $refund = $this->processRefund($order, $refundAmount, $reason, $method, [
                'items' => $items,
            ]);

            DB::commit();

            return $refund;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore inventory for refunded items
     *
     * @param Order $order
     * @param array|null $items Specific items to restore, or null for all
     * @return void
     */
    protected function restoreInventory(Order $order, ?array $items = null): void
    {
        if ($items === null) {
            // Restore all items
            foreach ($order->items as $orderItem) {
                $this->restoreItemInventory($orderItem, $orderItem->quantity, $order);
            }
        } else {
            // Restore specific items
            foreach ($items as $itemData) {
                $orderItem = OrderItem::find($itemData['item_id']);
                if ($orderItem) {
                    $this->restoreItemInventory($orderItem, $itemData['quantity'], $order);
                }
            }
        }
    }

    /**
     * Restore inventory for a single item
     *
     * @param OrderItem $orderItem
     * @param int $quantity
     * @param Order $order
     * @return void
     */
    protected function restoreItemInventory(OrderItem $orderItem, int $quantity, Order $order): void
    {
        $product = $orderItem->variant ?? $orderItem->product;

        if ($product && $product->track_inventory) {
            $this->inventoryService->processReturn($product, $quantity, [
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Refund - Order #{$order->order_number}",
            ]);
        }
    }

    /**
     * Record cash drawer movement for refund
     *
     * @param Order $order
     * @param float $amount
     * @return void
     */
    protected function recordCashMovement(Order $order, float $amount): void
    {
        $session = CashDrawerSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        if ($session) {
            CashMovement::create([
                'session_id' => $session->id,
                'type' => 'refund',
                'amount' => -$amount, // Negative for refund
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Refund for Order #{$order->order_number}",
            ]);
        }
    }

    /**
     * Validate refund method
     *
     * @param string $method
     * @return bool
     */
    public function isValidRefundMethod(string $method): bool
    {
        $validMethods = ['cash', 'card', 'store_credit'];
        return in_array($method, $validMethods);
    }

    /**
     * Get available refund methods
     *
     * @return array
     */
    public function getAvailableRefundMethods(): array
    {
        return [
            'cash' => 'Cash',
            'card' => 'Card Refund',
            'store_credit' => 'Store Credit',
        ];
    }

    /**
     * Calculate maximum refundable amount for an order
     *
     * @param Order $order
     * @return float
     */
    public function getMaxRefundableAmount(Order $order): float
    {
        $totalRefunded = $order->refunds()->sum('amount');
        return max(0, $order->total - $totalRefunded);
    }

    /**
     * Check if order can be refunded
     *
     * @param Order $order
     * @return array ['can_refund' => bool, 'reason' => string]
     */
    public function canRefund(Order $order): array
    {
        if ($order->status === 'refunded') {
            return ['can_refund' => false, 'reason' => 'Order has already been fully refunded'];
        }

        if ($order->status === 'cancelled') {
            return ['can_refund' => false, 'reason' => 'Cannot refund a cancelled order'];
        }

        if ($order->status !== 'completed') {
            return ['can_refund' => false, 'reason' => 'Only completed orders can be refunded'];
        }

        $maxRefundable = $this->getMaxRefundableAmount($order);
        if ($maxRefundable <= 0) {
            return ['can_refund' => false, 'reason' => 'Order has been fully refunded'];
        }

        // Check refund time limit if configured
        $refundDays = config('pos.refund_days_limit', null);
        if ($refundDays !== null) {
            $orderDate = $order->created_at;
            $cutoffDate = now()->subDays($refundDays);
            
            if ($orderDate->lt($cutoffDate)) {
                return [
                    'can_refund' => false,
                    'reason' => "Refund period has expired ({$refundDays} days)",
                ];
            }
        }

        return ['can_refund' => true, 'reason' => ''];
    }

    /**
     * Get refund statistics for date range
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function getRefundStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $refunds = Refund::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->get();

        $byMethod = $refunds->groupBy('refund_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        });

        return [
            'total_refunds' => $refunds->count(),
            'total_amount' => $refunds->sum('amount'),
            'average_refund' => $refunds->avg('amount'),
            'by_method' => $byMethod->toArray(),
        ];
    }

    /**
     * Get refund history for an order
     *
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRefundHistory(Order $order)
    {
        return $order->refunds()->with('user')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Calculate refund rate for a period
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function calculateRefundRate(\DateTime $startDate, \DateTime $endDate): array
    {
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        $refundedOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'refunded')
            ->count();

        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total');

        $totalRefunded = Refund::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('amount');

        $refundRate = $totalOrders > 0 ? ($refundedOrders / $totalOrders) * 100 : 0;
        $refundValueRate = $totalRevenue > 0 ? ($totalRefunded / $totalRevenue) * 100 : 0;

        return [
            'total_orders' => $totalOrders,
            'refunded_orders' => $refundedOrders,
            'refund_rate' => $refundRate,
            'total_revenue' => $totalRevenue,
            'total_refunded' => $totalRefunded,
            'refund_value_rate' => $refundValueRate,
        ];
    }
}