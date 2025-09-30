<?php

namespace App\Models;

use App\Models\Traits\HasWooCommerceSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Product extends Model
{
    use HasFactory, HasWooCommerceSync;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'woocommerce_id',
        'sku',
        'name',
        'description',
        'type',
        'price',
        'cost_price',
        'category_id',
        'tax_rate',
        'is_active',
        'track_inventory',
        'image_url',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the category that owns the product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Get the product variants
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get all barcodes for this product
     */
    public function barcodes(): MorphMany
    {
        return $this->morphMany(Barcode::class, 'barcodeable');
    }

    /**
     * Get the inventory record for this product
     */
    public function inventory(): MorphOne
    {
        return $this->morphOne(Inventory::class, 'inventoriable');
    }

    /**
     * Get all order items for this product
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if product is a variable product
     */
    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }

    /**
     * Check if product is a simple product
     */
    public function isSimple(): bool
    {
        return $this->type === 'simple';
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
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        return $this->available_stock > 0;
    }

    /**
     * Check if product is low on stock
     */
    public function isLowStock(): bool
    {
        if (!$this->track_inventory || !$this->inventory) {
            return false;
        }

        return $this->inventory->quantity <= $this->inventory->low_stock_threshold;
    }

    /**
     * Calculate price with tax
     */
    public function getPriceWithTaxAttribute(): float
    {
        return $this->price * (1 + ($this->tax_rate / 100));
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
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only simple products
     */
    public function scopeSimple($query)
    {
        return $query->where('type', 'simple');
    }

    /**
     * Scope to get only variable products
     */
    public function scopeVariable($query)
    {
        return $query->where('type', 'variable');
    }

    /**
     * Scope to get products in stock
     */
    public function scopeInStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->whereRaw('quantity > reserved_quantity');
        });
    }

    /**
     * Scope to get low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
                     ->whereHas('inventory', function ($q) {
                         $q->whereRaw('quantity <= low_stock_threshold');
                     });
    }

    /**
     * Scope to search products by name or SKU
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}