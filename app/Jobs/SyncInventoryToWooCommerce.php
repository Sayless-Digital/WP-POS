<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\WooCommerce\InventorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInventoryToWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;
    public int $backoff = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InventorySyncService $syncService): void
    {
        Log::info('Syncing inventory to WooCommerce', [
            'product_id' => $this->product->id,
            'sku' => $this->product->sku
        ]);

        try {
            $result = $syncService->syncProductInventory($this->product);

            if ($result['success']) {
                Log::info('Inventory synced successfully', [
                    'product_id' => $this->product->id
                ]);
            } else {
                Log::warning('Inventory sync failed', [
                    'product_id' => $this->product->id,
                    'message' => $result['message'] ?? 'Unknown error'
                ]);

                throw new \Exception($result['message'] ?? 'Failed to sync inventory');
            }
        } catch (\Exception $e) {
            Log::error('Inventory sync job exception', [
                'product_id' => $this->product->id,
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
        Log::error('Inventory sync job failed permanently', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage()
        ]);
    }
}