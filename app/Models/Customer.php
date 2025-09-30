<?php

namespace App\Models;

use App\Models\Traits\HasWooCommerceSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasWooCommerceSync;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'woocommerce_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'customer_group_id',
        'loyalty_points',
        'total_spent',
        'total_orders',
        'notes',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'loyalty_points' => 'integer',
        'total_spent' => 'decimal:2',
        'total_orders' => 'integer',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the customer group
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * Get all orders for this customer
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get completed orders
     */
    public function completedOrders(): HasMany
    {
        return $this->orders()->where('status', 'completed');
    }

    /**
     * Get the full name
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the display name (full name or email)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: ($this->email ?: 'Guest Customer');
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValueAttribute(): float
    {
        if ($this->total_orders === 0) {
            return 0;
        }

        return $this->total_spent / $this->total_orders;
    }

    /**
     * Add loyalty points
     */
    public function addLoyaltyPoints(int $points): void
    {
        $this->loyalty_points += $points;
        $this->save();
    }

    /**
     * Redeem loyalty points
     */
    public function redeemLoyaltyPoints(int $points): bool
    {
        if ($this->loyalty_points < $points) {
            return false;
        }

        $this->loyalty_points -= $points;
        $this->save();

        return true;
    }

    /**
     * Calculate loyalty points for amount
     */
    public function calculateLoyaltyPoints(float $amount): int
    {
        // 1 point per dollar spent (can be configured)
        return (int) floor($amount);
    }

    /**
     * Calculate discount from loyalty points
     */
    public function calculateLoyaltyDiscount(int $points): float
    {
        // 100 points = $1 discount (can be configured)
        return $points / 100;
    }

    /**
     * Update customer statistics after order
     */
    public function updateStatistics(float $orderTotal): void
    {
        $this->total_spent += $orderTotal;
        $this->total_orders += 1;
        
        // Add loyalty points
        $points = $this->calculateLoyaltyPoints($orderTotal);
        $this->loyalty_points += $points;
        
        $this->save();
    }

    /**
     * Get applicable discount percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        return $this->customerGroup?->discount_percentage ?? 0;
    }

    /**
     * Calculate discounted price
     */
    public function calculateDiscountedPrice(float $price): float
    {
        if (!$this->customerGroup) {
            return $price;
        }

        return $this->customerGroup->calculateDiscountedPrice($price);
    }

    /**
     * Check if customer is a VIP (high value customer)
     */
    public function isVip(float $threshold = 1000): bool
    {
        return $this->total_spent >= $threshold;
    }

    /**
     * Check if customer is active (has recent orders)
     */
    public function isActive(int $days = 90): bool
    {
        return $this->orders()
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }

    /**
     * Scope to get VIP customers
     */
    public function scopeVip($query, float $threshold = 1000)
    {
        return $query->where('total_spent', '>=', $threshold);
    }

    /**
     * Scope to get active customers
     */
    public function scopeActive($query, int $days = 90)
    {
        return $query->whereHas('orders', function ($q) use ($days) {
            $q->where('created_at', '>=', now()->subDays($days));
        });
    }

    /**
     * Scope to search customers
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to get customers with loyalty points
     */
    public function scopeWithLoyaltyPoints($query, int $minPoints = 1)
    {
        return $query->where('loyalty_points', '>=', $minPoints);
    }

    /**
     * Scope to order by total spent
     */
    public function scopeOrderBySpent($query, string $direction = 'desc')
    {
        return $query->orderBy('total_spent', $direction);
    }
}