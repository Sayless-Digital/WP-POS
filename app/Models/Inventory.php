<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inventoriable_type',
        'inventoriable_id',
        'quantity',
        'reserved_quantity',
        'reorder_point',
        'reorder_quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
    ];

    /**
     * Get the parent inventoriable model (Product or ProductVariant)
     */
    public function inventoriable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all stock movements for this inventory
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get available quantity (quantity - reserved)
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Check if inventory is in stock
     */
    public function isInStock(): bool
    {
        return $this->available_quantity > 0;
    }

    /**
     * Check if inventory is low on stock
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }

    /**
     * Check if inventory is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Adjust inventory quantity
     */
    public function adjustQuantity(int $amount, string $reason, ?int $userId = null): void
    {
        $oldQuantity = $this->quantity;
        $this->quantity += $amount;
        $this->save();

        // Create stock movement record
        $this->stockMovements()->create([
            'type' => $amount > 0 ? 'in' : 'out',
            'quantity' => abs($amount),
            'reason' => $reason,
            'user_id' => $userId,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $this->quantity,
        ]);
    }

    /**
     * Reserve inventory quantity
     */
    public function reserve(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->reserved_quantity += $quantity;
        $this->save();

        return true;
    }

    /**
     * Release reserved inventory
     */
    public function release(int $quantity): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->save();
    }

    /**
     * Fulfill reserved inventory (convert reserved to actual reduction)
     */
    public function fulfill(int $quantity, string $reason, ?int $userId = null): void
    {
        $this->release($quantity);
        $this->adjustQuantity(-$quantity, $reason, $userId);
    }

    /**
     * Perform physical count and adjust
     */
    public function physicalCount(int $countedQuantity, ?int $userId = null): void
    {
        $difference = $countedQuantity - $this->quantity;
        
        if ($difference !== 0) {
            $this->adjustQuantity(
                $difference,
                'Physical count adjustment',
                $userId
            );
        }

        $this->last_counted_at = now();
        $this->save();
    }

    /**
     * Scope to get low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= low_stock_threshold');
    }

    /**
     * Scope to get out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Scope to get in stock items
     */
    public function scopeInStock($query)
    {
        return $query->whereRaw('quantity > reserved_quantity');
    }

    /**
     * Scope to get items with reserved quantity
     */
    public function scopeWithReserved($query)
    {
        return $query->where('reserved_quantity', '>', 0);
    }

    /**
     * Scope for products
     */
    public function scopeForProducts($query)
    {
        return $query->where('inventoriable_type', Product::class);
    }

    /**
     * Scope for variants
     */
    public function scopeForVariants($query)
    {
        return $query->where('inventoriable_type', ProductVariant::class);
    }
}