<?php

namespace App\Services\WooCommerce;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;

class InventorySyncService
{
    protected WooCommerceClient $client;

    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sync inventory for a product to WooCommerce
     */
    public function syncProductInventory(Product $product): array
    {
        if (!$product->woocommerce_id) {
            return [
                'success' => false,
                'message' => 'Product not synced to WooCommerce'
            ];
        }

        try {
            if ($product->type === 'simple') {
                return $this->syncSimpleProductInventory($product);
            } else {
                return $this->syncVariableProductInventory($product);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync product inventory', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync simple product inventory
     */
    protected function syncSimpleProductInventory(Product $product): array
    {
        $inventory = $product->inventory;

        if (!$inventory) {
            return [
                'success' => false,
                'message' => 'No inventory record found'
            ];
        }

        $data = [
            'stock_quantity' => $inventory->quantity,
            'manage_stock' => true,
            'stock_status' => $inventory->quantity > 0 ? 'instock' : 'outofstock',
        ];

        $response = $this->client->put("products/{$product->woocommerce_id}", $data);

        if ($response['success']) {
            Log::info('Product inventory synced', [
                'product_id' => $product->id,
                'quantity' => $inventory->quantity
            ]);
        }

        return $response;
    }

    /**
     * Sync variable product inventory (all variants)
     */
    protected function syncVariableProductInventory(Product $product): array
    {
        $results = [];
        $allSuccess = true;

        foreach ($product->variants as $variant) {
            $result = $this->syncVariantInventory($variant);
            $results[] = $result;
            
            if (!$result['success']) {
                $allSuccess = false;
            }
        }

        return [
            'success' => $allSuccess,
            'results' => $results
        ];
    }

    /**
     * Sync variant inventory
     */
    public function syncVariantInventory(ProductVariant $variant): array
    {
        if (!$variant->woocommerce_id || !$variant->product->woocommerce_id) {
            return [
                'success' => false,
                'message' => 'Variant not synced to WooCommerce'
            ];
        }

        try {
            $inventory = $variant->inventory;

            if (!$inventory) {
                return [
                    'success' => false,
                    'message' => 'No inventory record found'
                ];
            }

            $data = [
                'stock_quantity' => $inventory->quantity,
                'manage_stock' => true,
                'stock_status' => $inventory->quantity > 0 ? 'instock' : 'outofstock',
            ];

            $response = $this->client->put(
                "products/{$variant->product->woocommerce_id}/variations/{$variant->woocommerce_id}",
                $data
            );

            if ($response['success']) {
                Log::info('Variant inventory synced', [
                    'variant_id' => $variant->id,
                    'quantity' => $inventory->quantity
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to sync variant inventory', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Import inventory from WooCommerce for a product
     */
    public function importProductInventory(Product $product): array
    {
        if (!$product->woocommerce_id) {
            return [
                'success' => false,
                'message' => 'Product not synced to WooCommerce'
            ];
        }

        try {
            $response = $this->client->get("products/{$product->woocommerce_id}");

            if (!$response['success']) {
                return $response;
            }

            $wooProduct = $response['data'];

            if ($product->type === 'simple' && isset($wooProduct['stock_quantity'])) {
                $this->updateInventory($product, (int) $wooProduct['stock_quantity']);
            } elseif ($product->type === 'variable') {
                $this->importVariantsInventory($product);
            }

            return [
                'success' => true,
                'message' => 'Inventory imported successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Import inventory for all variants
     */
    protected function importVariantsInventory(Product $product): void
    {
        $response = $this->client->get("products/{$product->woocommerce_id}/variations");

        if (!$response['success']) {
            return;
        }

        $wooVariations = $response['data'];

        foreach ($wooVariations as $wooVariation) {
            $variant = ProductVariant::where('woocommerce_id', $wooVariation['id'])->first();

            if ($variant && isset($wooVariation['stock_quantity'])) {
                $this->updateInventory($variant, (int) $wooVariation['stock_quantity']);
            }
        }
    }

    /**
     * Update inventory record
     */
    protected function updateInventory($model, int $quantity): void
    {
        $inventory = Inventory::firstOrNew([
            'inventoriable_type' => get_class($model),
            'inventoriable_id' => $model->id,
        ]);

        $oldQuantity = $inventory->quantity ?? 0;
        $inventory->quantity = $quantity;
        $inventory->save();

        // Log stock movement if quantity changed
        if ($oldQuantity !== $quantity) {
            StockMovement::create([
                'inventoriable_type' => get_class($model),
                'inventoriable_id' => $model->id,
                'type' => 'adjustment',
                'quantity' => $quantity - $oldQuantity,
                'reference_type' => 'woocommerce_sync',
                'notes' => 'Synced from WooCommerce',
            ]);
        }
    }

    /**
     * Batch sync inventory for multiple products
     */
    public function batchSyncInventory(array $productIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($productIds as $productId) {
            $product = Product::find($productId);

            if (!$product) {
                $results['failed']++;
                $results['errors'][] = "Product {$productId} not found";
                continue;
            }

            $result = $this->syncProductInventory($product);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $result['message'] ?? 'Unknown error';
            }
        }

        return $results;
    }

    /**
     * Sync inventory after a sale
     */
    public function syncAfterSale(Product $product, int $quantitySold): array
    {
        if (!config('woocommerce.inventory.sync_on_sale', true)) {
            return [
                'success' => true,
                'message' => 'Real-time sync disabled'
            ];
        }

        return $this->syncProductInventory($product);
    }

    /**
     * Check and sync low stock products
     */
    public function syncLowStockProducts(): array
    {
        $threshold = config('woocommerce.inventory.low_stock_threshold', 10);
        
        $products = Product::whereHas('inventory', function ($query) use ($threshold) {
            $query->where('quantity', '<=', $threshold);
        })->where('woocommerce_id', '!=', null)->get();

        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            $result = $this->syncProductInventory($product);
            
            if ($result['success']) {
                $synced++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => true,
            'synced' => $synced,
            'failed' => $failed,
            'total' => $products->count()
        ];
    }

    /**
     * Sync all inventory to WooCommerce
     */
    public function syncAllInventory(): array
    {
        $products = Product::whereNotNull('woocommerce_id')
            ->where('track_inventory', true)
            ->get();

        $stats = [
            'total' => $products->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($products as $product) {
            try {
                $result = $this->syncProductInventory($product);
                
                if ($result['success']) {
                    $stats['success']++;
                } else {
                    $stats['failed']++;
                    $stats['errors'][] = [
                        'product_id' => $product->id,
                        'error' => $result['message'] ?? 'Unknown error'
                    ];
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Inventory sync completed', $stats);

        return [
            'success' => true,
            'stats' => $stats
        ];
    }
}