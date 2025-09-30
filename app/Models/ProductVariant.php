<?php

namespace App\Models;

use App\Models\Traits\HasWooCommerceSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ProductVariant extends Model
{
    use HasFactory, HasWooCommerceSync;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'woocommerce_id',
        'sku',
        'name',
        'attributes',
        'price',
        'cost_price',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all barcodes for this variant
     */
    public function barcodes(): MorphMany
    {
        return $this->morphMany(Barcode::class, 'barcodeable');
    }

    /**
     * Get the inventory record for this variant
     */
    public function inventory(): MorphOne
    {
        return $this->morphOne(Inventory::class, 'inventoriable');
    }

    /**
     * Get all order items for this variant
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    /**
     * Get the current stock quantity
     */
    public function getStockQuantityAttribute(): int
    {
        return $this->inventory?->quantity ?? 0;
    }

    /**
     * Get the available stock (quantity - reserved)
     */
    public function getAvailableStockAttribute(): int
    {
        if (!$this->inventory) {
            return 0;
        }

        return $this->inventory->quantity - $this->inventory->reserved_quantity;
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        if (!$this->product->track_inventory) {
            return true;
        }

        return $this->available_stock > 0;
    }

    /**
     * Check if variant is low on stock
     */
    public function isLowStock(): bool
    {
        if (!$this->product->track_inventory || !$this->inventory) {
            return false;
        }

        return $this->inventory->quantity <= $this->inventory->low_stock_threshold;
    }

    /**
     * Calculate price with tax
     */
    public function getPriceWithTaxAttribute(): float
    {
        return $this->price * (1 + ($this->product->tax_rate / 100));
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price == 0) {
            return null;
        }

        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Get formatted attribute string (e.g., "Size: Large, Color: Red")
     */
    public function getFormattedAttributesAttribute(): string
    {
        if (!$this->attributes) {
            return '';
        }

        return collect($this->attributes)
            ->map(fn($value, $key) => "{$key}: {$value}")
            ->implode(', ');
    }

    /**
     * Get full display name (product name + variant attributes)
     */
    public function getFullNameAttribute(): string
    {
        if ($this->formatted_attributes) {
            return "{$this->product->name} ({$this->formatted_attributes})";
        }

        return $this->name;
    }

    /**
     * Scope to get only active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get variants in stock
     */
    public function scopeInStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->whereRaw('quantity > reserved_quantity');
        });
    }

    /**
     * Scope to get low stock variants
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('track_inventory', true);
        })->whereHas('inventory', function ($q) {
            $q->whereRaw('quantity <= low_stock_threshold');
        });
    }
}