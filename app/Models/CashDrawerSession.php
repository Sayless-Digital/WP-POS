<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashDrawerSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'opened_at',
        'closed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the user who opened the session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all cash movements for this session
     */
    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    /**
     * Get all orders during this session
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id')
                    ->whereBetween('created_at', [
                        $this->opened_at,
                        $this->closed_at ?? now()
                    ]);
    }

    /**
     * Check if session is open
     */
    public function isOpen(): bool
    {
        return is_null($this->closed_at);
    }

    /**
     * Check if session is closed
     */
    public function isClosed(): bool
    {
        return !is_null($this->closed_at);
    }

    /**
     * Get total cash in movements
     */
    public function getTotalCashInAttribute(): float
    {
        return $this->cashMovements()
                    ->where('type', 'in')
                    ->sum('amount');
    }

    /**
     * Get total cash out movements
     */
    public function getTotalCashOutAttribute(): float
    {
        return $this->cashMovements()
                    ->where('type', 'out')
                    ->sum('amount');
    }

    /**
     * Get total cash sales during session
     */
    public function getTotalCashSalesAttribute(): float
    {
        return $this->orders()
                    ->whereHas('payments', function ($query) {
                        $query->where('payment_method', 'cash');
                    })
                    ->sum('total');
    }

    /**
     * Calculate expected closing amount
     */
    public function calculateExpectedAmount(): float
    {
        return $this->opening_amount 
             + $this->total_cash_sales 
             + $this->total_cash_in 
             - $this->total_cash_out;
    }

    /**
     * Close the session
     */
    public function close(float $closingAmount, ?string $notes = null): void
    {
        $this->expected_amount = $this->calculateExpectedAmount();
        $this->closing_amount = $closingAmount;
        $this->difference = $closingAmount - $this->expected_amount;
        $this->closed_at = now();
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        $this->save();
    }

    /**
     * Check if there's a discrepancy
     */
    public function hasDiscrepancy(float $tolerance = 0.01): bool
    {
        return abs($this->difference) > $tolerance;
    }

    /**
     * Check if cash is over
     */
    public function isOver(): bool
    {
        return $this->difference > 0;
    }

    /**
     * Check if cash is short
     */
    public function isShort(): bool
    {
        return $this->difference < 0;
    }

    /**
     * Get session duration in minutes
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->closed_at) {
            return null;
        }

        return $this->opened_at->diffInMinutes($this->closed_at);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        return sprintf('%dh %dm', $hours, $minutes);
    }

    /**
     * Scope to get open sessions
     */
    public function scopeOpen($query)
    {
        return $query->whereNull('closed_at');
    }

    /**
     * Scope to get closed sessions
     */
    public function scopeClosed($query)
    {
        return $query->whereNotNull('closed_at');
    }

    /**
     * Scope to get sessions by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get sessions with discrepancies
     */
    public function scopeWithDiscrepancies($query, float $tolerance = 0.01)
    {
        return $query->whereNotNull('closed_at')
                     ->whereRaw('ABS(difference) > ?', [$tolerance]);
    }

    /**
     * Scope to get sessions within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('opened_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's sessions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (!$session->opened_at) {
                $session->opened_at = now();
            }
        });
    }
}