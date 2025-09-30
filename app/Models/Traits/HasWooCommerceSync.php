<?php

namespace App\Models\Traits;

trait HasWooCommerceSync
{
    /**
     * Check if the model is synced with WooCommerce
     */
    public function isSynced(): bool
    {
        return !is_null($this->woocommerce_id) && !is_null($this->synced_at);
    }

    /**
     * Mark the model as synced
     */
    public function markAsSynced(int $woocommerceId): void
    {
        $this->update([
            'woocommerce_id' => $woocommerceId,
            'synced_at' => now(),
        ]);
    }

    /**
     * Check if the model needs syncing
     */
    public function needsSync(): bool
    {
        if (is_null($this->synced_at)) {
            return true;
        }

        return $this->updated_at > $this->synced_at;
    }

    /**
     * Scope to get unsynced records
     */
    public function scopeUnsynced($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('synced_at')
              ->orWhereColumn('updated_at', '>', 'synced_at');
        });
    }

    /**
     * Scope to get synced records
     */
    public function scopeSynced($query)
    {
        return $query->whereNotNull('woocommerce_id')
                     ->whereNotNull('synced_at')
                     ->whereColumn('updated_at', '<=', 'synced_at');
    }
}