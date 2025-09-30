<?php

namespace App\Http\Controllers;

use App\Services\WooCommerce\ProductSyncService;
use App\Services\WooCommerce\OrderSyncService;
use App\Services\WooCommerce\CustomerSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WooCommerceWebhookController extends Controller
{
    /**
     * Handle incoming WooCommerce webhooks
     */
    public function handle(Request $request, string $topic)
    {
        // Verify webhook signature
        if (!$this->verifyWebhook($request)) {
            Log::warning('Invalid webhook signature', [
                'topic' => $topic,
                'ip' => $request->ip()
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        
        Log::info('Webhook received', [
            'topic' => $topic,
            'id' => $payload['id'] ?? null
        ]);

        try {
            $result = match($topic) {
                'product.created', 'product.updated' => $this->handleProductWebhook($payload),
                'product.deleted' => $this->handleProductDelete($payload),
                'order.created', 'order.updated' => $this->handleOrderWebhook($payload),
                'customer.created', 'customer.updated' => $this->handleCustomerWebhook($payload),
                default => ['success' => false, 'message' => 'Unknown webhook topic']
            };

            if ($result['success']) {
                return response()->json(['success' => true]);
            }

            return response()->json(['error' => $result['message'] ?? 'Processing failed'], 500);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle product webhook
     */
    protected function handleProductWebhook(array $payload): array
    {
        try {
            $syncService = app(ProductSyncService::class);
            return $syncService->importProduct($payload);
        } catch (\Exception $e) {
            Log::error('Product webhook failed', [
                'product_id' => $payload['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle product deletion
     */
    protected function handleProductDelete(array $payload): array
    {
        try {
            $product = \App\Models\Product::where('woocommerce_id', $payload['id'])->first();
            
            if ($product) {
                $product->update(['is_active' => false]);
                
                Log::info('Product deactivated from webhook', [
                    'product_id' => $product->id,
                    'woo_id' => $payload['id']
                ]);
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle order webhook
     */
    protected function handleOrderWebhook(array $payload): array
    {
        try {
            $syncService = app(OrderSyncService::class);
            return $syncService->importOrder($payload);
        } catch (\Exception $e) {
            Log::error('Order webhook failed', [
                'order_id' => $payload['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle customer webhook
     */
    protected function handleCustomerWebhook(array $payload): array
    {
        try {
            $syncService = app(CustomerSyncService::class);
            return $syncService->importCustomer($payload);
        } catch (\Exception $e) {
            Log::error('Customer webhook failed', [
                'customer_id' => $payload['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    protected function verifyWebhook(Request $request): bool
    {
        if (!config('woocommerce.webhooks.enabled', true)) {
            return true; // Skip verification if webhooks disabled
        }

        $secret = config('woocommerce.webhooks.secret');
        
        if (empty($secret)) {
            return true; // Skip verification if no secret configured
        }

        $signature = $request->header('X-WC-Webhook-Signature');
        
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expectedSignature, $signature);
    }
}