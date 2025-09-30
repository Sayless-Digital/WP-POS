<?php

namespace App\Models;

use App\Models\Traits\HasWooCommerceSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasWooCommerceSync;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'woocommerce_id',
        'order_number',
        'customer_id',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'payment_status',
        'notes',
        'is_synced',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all refunds
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Calculate order totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax_amount = $this->items->sum(function ($item) {
            return $item->subtotal * ($item->tax_rate / 100);
        });
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
    }

    /**
     * Get total paid amount
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments->sum('amount');
    }

    /**
     * Get total refunded amount
     */
    public function getTotalRefundedAttribute(): float
    {
        return $this->refunds->sum('amount');
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->total - $this->total_paid + $this->total_refunded);
    }

    /**
     * Check if order is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->remaining_balance <= 0.01; // Allow for rounding errors
    }

    /**
     * Check if order is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->total_paid > 0 && !$this->isFullyPaid();
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Complete the order
     */
    public function complete(): void
    {
        $this->status = 'completed';
        $this->payment_status = 'paid';
        $this->save();

        // Update customer statistics
        if ($this->customer) {
            $this->customer->updateStatistics($this->total);
        }
    }

    /**
     * Cancel the order
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();

        // Release reserved inventory
        foreach ($this->items as $item) {
            $inventoriable = $item->variant_id 
                ? $item->variant 
                : $item->product;

            if ($inventoriable && $inventoriable->inventory) {
                $inventoriable->inventory->release($item->quantity);
            }
        }
    }

    /**
     * Add payment to order
     */
    public function addPayment(string $method, float $amount, ?string $reference = null): Payment
    {
        $payment = $this->payments()->create([
            'payment_method' => $method,
            'amount' => $amount,
            'reference' => $reference,
        ]);

        $this->updatePaymentStatus();

        return $payment;
    }

    /**
     * Update payment status based on payments
     */
    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid()) {
            $this->payment_status = 'paid';
        } elseif ($this->isPartiallyPaid()) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }

        $this->save();
    }

    /**
     * Process refund
     */
    public function processRefund(float $amount, string $reason, ?int $userId = null): Refund
    {
        $refund = $this->refunds()->create([
            'amount' => $amount,
            'reason' => $reason,
            'user_id' => $userId,
        ]);

        // Update order status if fully refunded
        if ($this->total_refunded >= $this->total) {
            $this->status = 'refunded';
            $this->payment_status = 'refunded';
            $this->save();
        }

        return $refund;
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Scope to get orders by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get orders within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's orders
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get unpaid orders
     */
    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope to get unsynced orders
     */
    public function scopeUnsynced($query)
    {
        return $query->where('is_synced', false);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }
}