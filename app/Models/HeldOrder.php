<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeldOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'reference',
        'items',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the user who held the order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the number of items
     */
    public function getItemCountAttribute(): int
    {
        return count($this->items);
    }

    /**
     * Get total quantity of all items
     */
    public function getTotalQuantityAttribute(): int
    {
        return collect($this->items)->sum('quantity');
    }

    /**
     * Convert held order to actual order
     */
    public function convertToOrder(): Order
    {
        $order = Order::create([
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'status' => 'pending',
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total' => $this->total,
            'notes' => $this->notes,
        ]);

        // Create order items
        foreach ($this->items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'sku' => $item['sku'],
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'discount_amount' => $item['discount_amount'] ?? 0,
                'subtotal' => $item['subtotal'],
                'total' => $item['total'],
            ]);
        }

        // Delete the held order
        $this->delete();

        return $order;
    }

    /**
     * Update held order items
     */
    public function updateItems(array $items): void
    {
        $this->items = $items;
        $this->calculateTotals();
        $this->save();
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $items = collect($this->items);
        
        $this->subtotal = $items->sum('subtotal');
        $this->tax_amount = $items->sum(function ($item) {
            return ($item['subtotal'] ?? 0) * (($item['tax_rate'] ?? 0) / 100);
        });
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
    }

    /**
     * Generate unique reference
     */
    public static function generateReference(): string
    {
        $prefix = 'HOLD';
        $timestamp = now()->format('His');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Scope to get held orders by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get held orders by customer
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to get recent held orders
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to search by reference
     */
    public function scopeByReference($query, string $reference)
    {
        return $query->where('reference', 'like', "%{$reference}%");
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($heldOrder) {
            if (!$heldOrder->reference) {
                $heldOrder->reference = self::generateReference();
            }
        });
    }
}