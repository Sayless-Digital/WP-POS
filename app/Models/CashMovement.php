<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
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
        'cash_drawer_session_id',
        'type',
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
     * Get the cash drawer session
     */
    public function cashDrawerSession(): BelongsTo
    {
        return $this->belongsTo(CashDrawerSession::class);
    }

    /**
     * Get the user who made the movement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is a cash in movement
     */
    public function isCashIn(): bool
    {
        return $this->type === 'in';
    }

    /**
     * Check if this is a cash out movement
     */
    public function isCashOut(): bool
    {
        return $this->type === 'out';
    }

    /**
     * Get type display name
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'in' => 'Cash In',
            'out' => 'Cash Out',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get reason display name
     */
    public function getReasonNameAttribute(): string
    {
        return match($this->reason) {
            'opening_float' => 'Opening Float',
            'closing_float' => 'Closing Float',
            'bank_deposit' => 'Bank Deposit',
            'expense' => 'Expense',
            'refund' => 'Refund',
            'correction' => 'Correction',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->reason)),
        };
    }

    /**
     * Get signed amount (negative for out, positive for in)
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->type === 'out' ? -$this->amount : $this->amount;
    }

    /**
     * Scope to get cash in movements
     */
    public function scopeCashIn($query)
    {
        return $query->where('type', 'in');
    }

    /**
     * Scope to get cash out movements
     */
    public function scopeCashOut($query)
    {
        return $query->where('type', 'out');
    }

    /**
     * Scope to get by reason
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope to get by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get by session
     */
    public function scopeBySession($query, int $sessionId)
    {
        return $query->where('cash_drawer_session_id', $sessionId);
    }

    /**
     * Scope to get movements within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's movements
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get recent movements
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            $movement->created_at = now();
        });
    }

    /**
     * Create a cash in movement
     */
    public static function cashIn(
        int $sessionId,
        float $amount,
        string $reason,
        int $userId,
        ?string $notes = null
    ): self {
        return self::create([
            'cash_drawer_session_id' => $sessionId,
            'type' => 'in',
            'amount' => $amount,
            'reason' => $reason,
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Create a cash out movement
     */
    public static function cashOut(
        int $sessionId,
        float $amount,
        string $reason,
        int $userId,
        ?string $notes = null
    ): self {
        return self::create([
            'cash_drawer_session_id' => $sessionId,
            'type' => 'out',
            'amount' => $amount,
            'reason' => $reason,
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }
}