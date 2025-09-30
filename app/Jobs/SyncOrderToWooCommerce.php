<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WooCommerce\OrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOrderToWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120; // 2 minutes
    public int $tries = 3;
    public int $backoff = 30; // 30 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OrderSyncService $syncService): void
    {
        Log::info('Syncing order to WooCommerce', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number
        ]);

        try {
            $result = $syncService->exportOrder($this->order);

            if ($result['success']) {
                Log::info('Order synced successfully', [
                    'order_id' => $this->order->id,
                    'woo_order_id' => $result['woo_order']['id'] ?? null
                ]);
            } else {
                Log::error('Order sync failed', [
                    'order_id' => $this->order->id,
                    'message' => $result['message'] ?? 'Unknown error'
                ]);

                throw new \Exception($result['message'] ?? 'Failed to sync order');
            }
        } catch (\Exception $e) {
            Log::error('Order sync job exception', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order sync job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage()
        ]);

        // Mark order as sync failed
        $this->order->update([
            'is_synced' => false,
            'notes' => ($this->order->notes ?? '') . "\n\nSync failed: " . $exception->getMessage()
        ]);
    }
}