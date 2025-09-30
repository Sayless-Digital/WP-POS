<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SyncQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    /**
     * Sync an offline order to the database
     */
    public function syncOfflineOrder(array $orderData): array
    {
        DB::beginTransaction();

        try {
            Log::info('Syncing offline order', ['order_number' => $orderData['orderNumber'] ?? 'unknown']);

            // Create the order
            $order = Order::create([
                'order_number' => $this->generateNewOrderNumber($orderData['orderNumber'] ?? null),
                'customer_id' => $orderData['customer_id'] ?? null,
                'user_id' => $orderData['user_id'] ?? auth()->id(),
                'status' => 'completed',
                'subtotal' => $orderData['subtotal'] ?? 0,
                'tax_amount' => $orderData['tax'] ?? 0,
                'discount_amount' => $orderData['discount'] ?? 0,
                'total' => $orderData['total'] ?? 0,
                'payment_status' => 'paid',
                'notes' => 'Synced from offline order: ' . ($orderData['orderNumber'] ?? 'N/A'),
                'created_at' => $orderData['timestamp'] ?? now(),
            ]);

            // Create order items
            if (isset($orderData['cart']) && is_array($orderData['cart'])) {
                foreach ($orderData['cart'] as $item) {
                    $orderItem = $order->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'product_variant_id' => $item['variant_id'] ?? null,
                        'sku' => $item['sku'] ?? '',
                        'name' => $item['name'] ?? '',
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? 0,
                        'tax_rate' => $item['tax_rate'] ?? 0,
                        'discount_amount' => $item['discount'] ?? 0,
                        'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                        'total' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1) * (1 + ($item['tax_rate'] ?? 0) / 100),
                    ]);

                    // Update inventory
                    if (isset($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        
                        if ($product && $product->track_inventory) {
                            $inventory = $product->inventory;
                            
                            if ($inventory) {
                                $inventory->decrement('quantity', $item['quantity'] ?? 1);

                                // Create stock movement
                                StockMovement::create([
                                    'inventoriable_type' => Product::class,
                                    'inventoriable_id' => $product->id,
                                    'type' => 'sale',
                                    'quantity' => -($item['quantity'] ?? 1),
                                    'reference_type' => Order::class,
                                    'reference_id' => $order->id,
                                    'user_id' => $orderData['user_id'] ?? auth()->id(),
                                    'notes' => 'Offline order sync',
                                ]);
                            }
                        }
                    }
                }
            }

            // Create payments
            if (isset($orderData['payments']) && is_array($orderData['payments'])) {
                foreach ($orderData['payments'] as $payment) {
                    $order->payments()->create([
                        'payment_method' => $payment['method'] ?? 'cash',
                        'amount' => $payment['amount'] ?? 0,
                        'reference' => $payment['reference'] ?? null,
                        'status' => 'completed',
                    ]);
                }
            }

            // Queue for WooCommerce sync if enabled
            if (config('woocommerce.sync.enabled')) {
                SyncQueue::create([
                    'syncable_type' => Order::class,
                    'syncable_id' => $order->id,
                    'action' => 'create',
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            Log::info('Offline order synced successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return [
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => 'Order synced successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Offline order sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to sync order',
            ];
        }
    }

    /**
     * Generate a new order number from offline order number
     */
    protected function generateNewOrderNumber(?string $offlineNumber): string
    {
        $prefix = config('pos.order_number_prefix', 'POS-');
        $date = now()->format('Ymd');
        $sequence = Order::whereDate('created_at', today())->count() + 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get product cache for offline use
     */
    public function getProductCache(): array
    {
        return Product::with(['inventory', 'barcodes'])
            ->where('is_active', true)
            ->select([
                'id',
                'sku',
                'name',
                'price',
                'cost',
                'tax_rate',
                'type',
                'image_url',
                'track_inventory',
                'category_id',
            ])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'cost' => (float) $product->cost,
                    'tax_rate' => (float) $product->tax_rate,
                    'type' => $product->type,
                    'image_url' => $product->image_url,
                    'track_inventory' => (bool) $product->track_inventory,
                    'category_id' => $product->category_id,
                    'quantity' => $product->inventory?->quantity ?? 0,
                    'barcode' => $product->barcodes->first()?->barcode,
                    'barcodes' => $product->barcodes->pluck('barcode')->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Sync inventory update from offline
     */
    public function syncInventoryUpdate(array $data): array
    {
        try {
            $product = Product::find($data['productId']);

            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'Product not found',
                ];
            }

            if (!$product->track_inventory) {
                return [
                    'success' => false,
                    'error' => 'Product does not track inventory',
                ];
            }

            $inventory = $product->inventory;

            if (!$inventory) {
                return [
                    'success' => false,
                    'error' => 'Inventory record not found',
                ];
            }

            // Update inventory
            $oldQuantity = $inventory->quantity;
            $inventory->quantity = $data['quantity'];
            $inventory->save();

            // Create stock movement
            StockMovement::create([
                'inventoriable_type' => Product::class,
                'inventoriable_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $data['quantity'] - $oldQuantity,
                'user_id' => auth()->id(),
                'notes' => $data['reason'] ?? 'Offline inventory sync',
            ]);

            Log::info('Inventory synced from offline', [
                'product_id' => $product->id,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $data['quantity'],
            ]);

            return [
                'success' => true,
                'product_id' => $product->id,
                'quantity' => $inventory->quantity,
            ];

        } catch (\Exception $e) {
            Log::error('Inventory sync failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve inventory conflict
     */
    public function resolveInventoryConflict(Product $product, int $offlineQuantity, int $onlineQuantity): int
    {
        // Strategy: Use the lower quantity to prevent overselling
        $resolvedQuantity = min($offlineQuantity, $onlineQuantity);

        $product->inventory->update(['quantity' => $resolvedQuantity]);

        Log::warning('Inventory conflict resolved', [
            'product_id' => $product->id,
            'offline_quantity' => $offlineQuantity,
            'online_quantity' => $onlineQuantity,
            'resolved_quantity' => $resolvedQuantity,
        ]);

        return $resolvedQuantity;
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(): array
    {
        return [
            'pending_sync_queue' => SyncQueue::where('status', 'pending')->count(),
            'failed_sync_queue' => SyncQueue::where('status', 'failed')->count(),
            'last_successful_sync' => SyncQueue::where('status', 'completed')
                ->latest('updated_at')
                ->value('updated_at'),
        ];
    }
}