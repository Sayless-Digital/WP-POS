<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncQueue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'syncable_type',
        'syncable_id',
        'action',
        'payload',
        'status',
        'attempts',
        'last_error',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the syncable model (Product, Order, Customer, etc.)
     */
    public function syncable()
    {
        return $this->morphTo();
    }

    /**
     * Check if sync is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if sync is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if sync is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if sync failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'synced_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
        ]);
    }

    /**
     * Retry the sync
     */
    public function retry(): void
    {
        $this->update([
            'status' => 'pending',
            'last_error' => null,
        ]);
    }

    /**
     * Check if should retry
     */
    public function shouldRetry(int $maxAttempts = 3): bool
    {
        return $this->isFailed() && $this->attempts < $maxAttempts;
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
            default => ucfirst($this->action),
        };
    }

    /**
     * Scope to get pending items
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get processing items
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to get completed items
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed items
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get items that should retry
     */
    public function scopeShouldRetry($query, int $maxAttempts = 3)
    {
        return $query->where('status', 'failed')
                     ->where('attempts', '<', $maxAttempts);
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
     * Scope to get oldest first
     */
    public function scopeOldestFirst($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope to get recent items
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}