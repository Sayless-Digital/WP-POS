<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\SyncQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CustomerService
{
    /**
     * Create a new customer
     *
     * @param array $data
     * @return Customer
     * @throws \Exception
     */
    public function createCustomer(array $data): Customer
    {
        DB::beginTransaction();

        try {
            // Check for duplicate email
            if (!empty($data['email'])) {
                $existing = Customer::where('email', $data['email'])->first();
                if ($existing) {
                    throw new \Exception("Customer with this email already exists");
                }
            }

            $customer = Customer::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'customer_group_id' => $data['customer_group_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Queue for WooCommerce sync
            SyncQueue::create([
                'syncable_type' => Customer::class,
                'syncable_id' => $customer->id,
                'action' => 'create',
                'status' => 'pending',
            ]);

            DB::commit();

            return $customer->load('customerGroup');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update customer information
     *
     * @param Customer $customer
     * @param array $data
     * @return Customer
     * @throws \Exception
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        DB::beginTransaction();

        try {
            // Check for duplicate email if changing
            if (!empty($data['email']) && $data['email'] !== $customer->email) {
                $existing = Customer::where('email', $data['email'])
                    ->where('id', '!=', $customer->id)
                    ->first();
                if ($existing) {
                    throw new \Exception("Customer with this email already exists");
                }
            }

            $customer->update([
                'first_name' => $data['first_name'] ?? $customer->first_name,
                'last_name' => $data['last_name'] ?? $customer->last_name,
                'email' => $data['email'] ?? $customer->email,
                'phone' => $data['phone'] ?? $customer->phone,
                'address' => $data['address'] ?? $customer->address,
                'city' => $data['city'] ?? $customer->city,
                'postal_code' => $data['postal_code'] ?? $customer->postal_code,
                'customer_group_id' => $data['customer_group_id'] ?? $customer->customer_group_id,
                'notes' => $data['notes'] ?? $customer->notes,
            ]);

            // Queue for sync
            SyncQueue::create([
                'syncable_type' => Customer::class,
                'syncable_id' => $customer->id,
                'action' => 'update',
                'status' => 'pending',
            ]);

            DB::commit();

            return $customer->fresh(['customerGroup']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Find or create customer
     *
     * @param array $data
     * @return Customer
     */
    public function findOrCreateCustomer(array $data): Customer
    {
        // Try to find by email first
        if (!empty($data['email'])) {
            $customer = Customer::where('email', $data['email'])->first();
            if ($customer) {
                return $customer;
            }
        }

        // Try to find by phone
        if (!empty($data['phone'])) {
            $customer = Customer::where('phone', $data['phone'])->first();
            if ($customer) {
                return $customer;
            }
        }

        // Create new customer
        return $this->createCustomer($data);
    }

    /**
     * Search customers
     *
     * @param string $query
     * @return Collection
     */
    public function searchCustomers(string $query): Collection
    {
        return Customer::where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
        })
            ->with('customerGroup')
            ->orderBy('first_name')
            ->limit(50)
            ->get();
    }

    /**
     * Get customer purchase history
     *
     * @param Customer $customer
     * @param int $limit
     * @return Collection
     */
    public function getPurchaseHistory(Customer $customer, int $limit = 20): Collection
    {
        return Order::where('customer_id', $customer->id)
            ->with(['items', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customer statistics
     *
     * @param Customer $customer
     * @return array
     */
    public function getCustomerStatistics(Customer $customer): array
    {
        $orders = Order::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->get();

        $lastOrder = $orders->sortByDesc('created_at')->first();
        $averageOrderValue = $orders->avg('total');
        $totalItems = $orders->sum(function ($order) {
            return $order->items->sum('quantity');
        });

        return [
            'total_orders' => $customer->total_orders,
            'total_spent' => $customer->total_spent,
            'average_order_value' => $averageOrderValue,
            'total_items_purchased' => $totalItems,
            'loyalty_points' => $customer->loyalty_points,
            'last_order_date' => $lastOrder?->created_at,
            'customer_since' => $customer->created_at,
            'days_since_last_order' => $lastOrder ? now()->diffInDays($lastOrder->created_at) : null,
        ];
    }

    /**
     * Award loyalty points
     *
     * @param Customer $customer
     * @param int $points
     * @param string|null $reason
     * @return Customer
     */
    public function awardLoyaltyPoints(Customer $customer, int $points, ?string $reason = null): Customer
    {
        $customer->increment('loyalty_points', $points);

        // Log the points award if needed
        if ($reason) {
            $customer->update([
                'notes' => $customer->notes . "\n" . now()->format('Y-m-d H:i') . ": Awarded {$points} points - {$reason}",
            ]);
        }

        return $customer->fresh();
    }

    /**
     * Redeem loyalty points
     *
     * @param Customer $customer
     * @param int $points
     * @return Customer
     * @throws \Exception
     */
    public function redeemLoyaltyPoints(Customer $customer, int $points): Customer
    {
        if ($customer->loyalty_points < $points) {
            throw new \Exception("Insufficient loyalty points. Available: {$customer->loyalty_points}");
        }

        $customer->decrement('loyalty_points', $points);

        return $customer->fresh();
    }

    /**
     * Calculate loyalty points for amount
     *
     * @param float $amount
     * @return int
     */
    public function calculateLoyaltyPoints(float $amount): int
    {
        $rate = config('pos.loyalty_points_rate', 0.01); // 1 point per $1 by default
        return (int) floor($amount * $rate);
    }

    /**
     * Get top customers by spending
     *
     * @param int $limit
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return Collection
     */
    public function getTopCustomers(int $limit = 10, ?\DateTime $startDate = null, ?\DateTime $endDate = null): Collection
    {
        $query = Customer::query();

        if ($startDate && $endDate) {
            // Calculate spending for date range
            $query->withSum(['orders as period_spent' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            }], 'total')
                ->orderBy('period_spent', 'desc');
        } else {
            // Use total_spent
            $query->orderBy('total_spent', 'desc');
        }

        return $query->with('customerGroup')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customers by group
     *
     * @param CustomerGroup $group
     * @return Collection
     */
    public function getCustomersByGroup(CustomerGroup $group): Collection
    {
        return Customer::where('customer_group_id', $group->id)
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Assign customer to group
     *
     * @param Customer $customer
     * @param CustomerGroup|null $group
     * @return Customer
     */
    public function assignToGroup(Customer $customer, ?CustomerGroup $group): Customer
    {
        $customer->update([
            'customer_group_id' => $group?->id,
        ]);

        return $customer->fresh(['customerGroup']);
    }

    /**
     * Get inactive customers
     *
     * @param int $days Number of days without purchase
     * @return Collection
     */
    public function getInactiveCustomers(int $days = 90): Collection
    {
        $cutoffDate = now()->subDays($days);

        return Customer::whereHas('orders', function ($query) use ($cutoffDate) {
            $query->where('created_at', '<', $cutoffDate);
        })
            ->orWhereDoesntHave('orders')
            ->with(['customerGroup', 'orders' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get();
    }

    /**
     * Get new customers for period
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return Collection
     */
    public function getNewCustomers(\DateTime $startDate, \DateTime $endDate): Collection
    {
        return Customer::whereBetween('created_at', [$startDate, $endDate])
            ->with('customerGroup')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get customer lifetime value segments
     *
     * @return array
     */
    public function getCustomerSegments(): array
    {
        return [
            'high_value' => Customer::where('total_spent', '>=', 1000)->count(),
            'medium_value' => Customer::whereBetween('total_spent', [500, 999.99])->count(),
            'low_value' => Customer::whereBetween('total_spent', [100, 499.99])->count(),
            'new' => Customer::where('total_spent', '<', 100)->count(),
        ];
    }

    /**
     * Merge duplicate customers
     *
     * @param Customer $primary
     * @param Customer $duplicate
     * @return Customer
     * @throws \Exception
     */
    public function mergeCustomers(Customer $primary, Customer $duplicate): Customer
    {
        DB::beginTransaction();

        try {
            // Transfer orders
            Order::where('customer_id', $duplicate->id)
                ->update(['customer_id' => $primary->id]);

            // Merge statistics
            $primary->increment('total_spent', $duplicate->total_spent);
            $primary->increment('total_orders', $duplicate->total_orders);
            $primary->increment('loyalty_points', $duplicate->loyalty_points);

            // Merge notes
            if ($duplicate->notes) {
                $primary->update([
                    'notes' => $primary->notes . "\n--- Merged from customer #{$duplicate->id} ---\n" . $duplicate->notes,
                ]);
            }

            // Delete duplicate
            $duplicate->delete();

            DB::commit();

            return $primary->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Export customer data
     *
     * @param Customer $customer
     * @return array
     */
    public function exportCustomerData(Customer $customer): array
    {
        return [
            'customer' => $customer->toArray(),
            'orders' => $customer->orders()->with('items')->get()->toArray(),
            'statistics' => $this->getCustomerStatistics($customer),
        ];
    }
}