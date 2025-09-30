<?php

namespace App\Jobs;

use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductsFromWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;
    public int $backoff = 60; // 1 minute between retries

    /**
     * Execute the job.
     */
    public function handle(ProductSyncService $syncService): void
    {
        Log::info('Starting scheduled product import from WooCommerce');

        try {
            $result = $syncService->importAll();

            if ($result['success']) {
                Log::info('Product import completed successfully', $result['stats']);
            } else {
                Log::error('Product import failed', [
                    'message' => $result['message'] ?? 'Unknown error',
                    'stats' => $result['stats'] ?? []
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Product import job exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Product import job failed permanently', [
            'error' => $exception->getMessage()
        ]);
    }
}