<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
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
        'syncable_type',
        'syncable_id',
        'action',
        'status',
        'request_data',
        'response_data',
        'error_message',
        'duration_ms',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the syncable model
     */
    public function syncable()
    {
        return $this->morphTo();
    }

    /**
     * Check if sync was successful
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if sync failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get action display name
     */
    public function getActionNameAttribute(): string
    {
        return match($this->action) {
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
            'fetch' => 'Fetch',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get duration in seconds
     */
    public function getDurationSecondsAttribute(): float
    {
        return $this->duration_ms / 1000;
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_ms < 1000) {
            return "{$this->duration_ms}ms";
        }

        return number_format($this->duration_seconds, 2) . 's';
    }

    /**
     * Scope to get successful syncs
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to get failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get by syncable type
     */
    public function scopeBySyncableType($query, string $type)
    {
        return $query->where('syncable_type', $type);
    }

    /**
     * Scope to get recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get today's logs
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get slow syncs (over threshold)
     */
    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }

    /**
     * Scope to order by duration
     */
    public function scopeOrderByDuration($query, string $direction = 'desc')
    {
        return $query->orderBy('duration_ms', $direction);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            $log->created_at = now();
        });
    }

    /**
     * Create a log entry
     */
    public static function createLog(
        string $syncableType,
        int $syncableId,
        string $action,
        string $status,
        ?array $requestData = null,
        ?array $responseData = null,
        ?string $errorMessage = null,
        ?int $durationMs = null
    ): self {
        return self::create([
            'syncable_type' => $syncableType,
            'syncable_id' => $syncableId,
            'action' => $action,
            'status' => $status,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
        ]);
    }
}