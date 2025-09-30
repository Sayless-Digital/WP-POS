<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
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
        'amount',
        'reason',
        'user_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
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
     * Get the user who processed the refund
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is a full refund
     */
    public function isFullRefund(): bool
    {
        return $this->amount >= $this->order->total;
    }

    /**
     * Check if this is a partial refund
     */
    public function isPartialRefund(): bool
    {
        return !$this->isFullRefund();
    }

    /**
     * Get refund percentage
     */
    public function getRefundPercentageAttribute(): float
    {
        if ($this->order->total == 0) {
            return 0;
        }

        return ($this->amount / $this->order->total) * 100;
    }

    /**
     * Scope to get refunds by reason
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope to get refunds by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get full refunds
     */
    public function scopeFullRefunds($query)
    {
        return $query->whereColumn('amount', '>=', function ($q) {
            $q->select('total')
              ->from('orders')
              ->whereColumn('orders.id', 'refunds.order_id');
        });
    }

    /**
     * Scope to get partial refunds
     */
    public function scopePartialRefunds($query)
    {
        return $query->whereColumn('amount', '<', function ($q) {
            $q->select('total')
              ->from('orders')
              ->whereColumn('orders.id', 'refunds.order_id');
        });
    }

    /**
     * Scope to get refunds within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's refunds
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            $refund->created_at = now();
        });
    }
}