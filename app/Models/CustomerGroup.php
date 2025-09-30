<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'discount_percentage',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    /**
     * Get all customers in this group
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get active customers in this group
     */
    public function activeCustomers(): HasMany
    {
        return $this->customers()->whereNotNull('email');
    }

    /**
     * Calculate discounted price
     */
    public function calculateDiscountedPrice(float $price): float
    {
        if ($this->discount_percentage <= 0) {
            return $price;
        }

        return $price * (1 - ($this->discount_percentage / 100));
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscountAmount(float $price): float
    {
        return $price - $this->calculateDiscountedPrice($price);
    }

    /**
     * Get total customers count
     */
    public function getTotalCustomersAttribute(): int
    {
        return $this->customers()->count();
    }

    /**
     * Get total spent by all customers in group
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->customers()->sum('total_spent');
    }

    /**
     * Get average spent per customer
     */
    public function getAverageSpentAttribute(): float
    {
        $count = $this->total_customers;
        
        if ($count === 0) {
            return 0;
        }

        return $this->total_spent / $count;
    }

    /**
     * Scope to get groups with discount
     */
    public function scopeWithDiscount($query)
    {
        return $query->where('discount_percentage', '>', 0);
    }

    /**
     * Scope to get groups without discount
     */
    public function scopeWithoutDiscount($query)
    {
        return $query->where('discount_percentage', '<=', 0);
    }

    /**
     * Scope to order by discount percentage
     */
    public function scopeOrderByDiscount($query, string $direction = 'desc')
    {
        return $query->orderBy('discount_percentage', $direction);
    }
}