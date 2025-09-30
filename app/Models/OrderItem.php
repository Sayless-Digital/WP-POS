<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'sku',
        'name',
        'quantity',
        'price',
        'tax_rate',
        'discount_amount',
        'subtotal',
        'total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the inventoriable item (product or variant)
     */
    public function getInventoriableAttribute()
    {
        return $this->variant_id ? $this->variant : $this->product;
    }

    /**
     * Calculate tax amount
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->subtotal * ($this->tax_rate / 100);
    }

    /**
     * Calculate line total with tax
     */
    public function getLineTotalAttribute(): float
    {
        return $this->subtotal + $this->tax_amount - $this->discount_amount;
    }

    /**
     * Calculate unit price after discount
     */
    public function getUnitPriceAfterDiscountAttribute(): float
    {
        if ($this->quantity === 0) {
            return 0;
        }

        return ($this->subtotal - $this->discount_amount) / $this->quantity;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->subtotal === 0) {
            return 0;
        }

        return ($this->discount_amount / $this->subtotal) * 100;
    }

    /**
     * Calculate totals
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->price * $this->quantity;
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
    }

    /**
     * Reserve inventory for this item
     */
    public function reserveInventory(): bool
    {
        $inventoriable = $this->inventoriable;

        if (!$inventoriable || !$inventoriable->inventory) {
            return true; // No inventory tracking
        }

        return $inventoriable->inventory->reserve($this->quantity);
    }

    /**
     * Fulfill inventory for this item
     */
    public function fulfillInventory(?int $userId = null): void
    {
        $inventoriable = $this->inventoriable;

        if (!$inventoriable || !$inventoriable->inventory) {
            return; // No inventory tracking
        }

        $inventoriable->inventory->fulfill(
            $this->quantity,
            "Order #{$this->order->order_number}",
            $userId
        );
    }

    /**
     * Release reserved inventory
     */
    public function releaseInventory(): void
    {
        $inventoriable = $this->inventoriable;

        if (!$inventoriable || !$inventoriable->inventory) {
            return; // No inventory tracking
        }

        $inventoriable->inventory->release($this->quantity);
    }

    /**
     * Scope to get items for a specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get items for a specific variant
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope to get items with discount
     */
    public function scopeWithDiscount($query)
    {
        return $query->where('discount_amount', '>', 0);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->created_at = now();
            
            if (!$item->subtotal) {
                $item->calculateTotals();
            }
        });

        static::created(function ($item) {
            // Update order totals
            $item->order->calculateTotals();
            $item->order->save();
        });

        static::updated(function ($item) {
            // Update order totals
            $item->order->calculateTotals();
            $item->order->save();
        });

        static::deleted(function ($item) {
            // Update order totals
            $item->order->calculateTotals();
            $item->order->save();
        });
    }
}