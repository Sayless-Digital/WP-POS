<?php

namespace App\Services\WooCommerce;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use App\Models\Barcode;
use App\Models\Inventory;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductSyncService
{
    protected WooCommerceClient $client;

    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }

    /**
     * Import all products from WooCommerce
     */
    public function importAll(): array
    {
        $startTime = now();
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            Log::info('Starting WooCommerce product import');

            // Get all products from WooCommerce
            $response = $this->client->getAll('products', [
                'status' => 'any',
                'orderby' => 'id',
                'order' => 'asc'
            ]);

            if (!$response['success']) {
                throw new \Exception($response['message'] ?? 'Failed to fetch products');
            }

            $products = $response['data'];
            $stats['total'] = count($products);

            foreach ($products as $wooProduct) {
                try {
                    $result = $this->importProduct($wooProduct);
                    
                    if ($result['created']) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $stats['errors'][] = [
                        'product_id' => $wooProduct['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to import product', [
                        'woo_product_id' => $wooProduct['id'] ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log sync result
            $this->logSync('product', 'import', $stats, $startTime);

            Log::info('WooCommerce product import completed', $stats);

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (\Exception $e) {
            Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->logSync('product', 'import', $stats, $startTime, 'failed', $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $stats
            ];
        }
    }

    /**
     * Import a single product from WooCommerce
     */
    public function importProduct(array $wooProduct): array
    {
        DB::beginTransaction();

        try {
            // Find or create product
            $product = Product::where('woocommerce_id', $wooProduct['id'])->first();
            $isNew = !$product;

            if (!$product) {
                $product = new Product();
            }

            // Map WooCommerce data to local product
            $product->woocommerce_id = $wooProduct['id'];
            $product->sku = $wooProduct['sku'] ?: 'WC-' . $wooProduct['id'];
            $product->name = $wooProduct['name'];
            $product->description = $wooProduct['description'] ?? null;
            $product->type = $wooProduct['type'] === 'variable' ? 'variable' : 'simple';
            $product->price = (float) $wooProduct['price'];
            $product->is_active = $wooProduct['status'] === 'publish';
            $product->track_inventory = $wooProduct['manage_stock'] ?? false;
            $product->synced_at = now();

            // Handle category
            if (!empty($wooProduct['categories'])) {
                $categoryId = $this->syncCategory($wooProduct['categories'][0]);
                $product->category_id = $categoryId;
            }

            // Handle image
            if (!empty($wooProduct['images'])) {
                $product->image_url = $wooProduct['images'][0]['src'] ?? null;
            }

            $product->save();

            // Sync inventory
            if ($product->type === 'simple' && $product->track_inventory) {
                $this->syncInventory($product, $wooProduct);
            }

            // Sync variations if variable product
            if ($product->type === 'variable') {
                $this->syncVariations($product, $wooProduct['id']);
            }

            // Sync barcode if available
            if (!empty($wooProduct['sku'])) {
                $this->syncBarcode($product, $wooProduct['sku']);
            }

            DB::commit();

            return [
                'success' => true,
                'created' => $isNew,
                'product' => $product
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Export product to WooCommerce
     */
    public function exportProduct(Product $product): array
    {
        try {
            $data = $this->mapProductToWooCommerce($product);

            if ($product->woocommerce_id) {
                // Update existing product
                $response = $this->client->put("products/{$product->woocommerce_id}", $data);
            } else {
                // Create new product
                $response = $this->client->post('products', $data);
            }

            if ($response['success']) {
                $wooProduct = $response['data'];
                
                $product->update([
                    'woocommerce_id' => $wooProduct['id'],
                    'synced_at' => now()
                ]);

                // Sync variations if variable product
                if ($product->type === 'variable') {
                    $this->exportVariations($product);
                }

                return [
                    'success' => true,
                    'product' => $product,
                    'woo_product' => $wooProduct
                ];
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to export product', [
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
     * Sync product variations
     */
    protected function syncVariations(Product $product, int $wooProductId): void
    {
        $response = $this->client->get("products/{$wooProductId}/variations");

        if (!$response['success']) {
            return;
        }

        $wooVariations = $response['data'];

        foreach ($wooVariations as $wooVariation) {
            $variant = ProductVariant::where('woocommerce_id', $wooVariation['id'])->first();

            if (!$variant) {
                $variant = new ProductVariant();
            }

            $variant->product_id = $product->id;
            $variant->woocommerce_id = $wooVariation['id'];
            $variant->sku = $wooVariation['sku'] ?: "WC-VAR-{$wooVariation['id']}";
            $variant->name = $this->buildVariantName($wooVariation);
            $variant->attributes = json_encode($wooVariation['attributes']);
            $variant->price = (float) $wooVariation['price'];
            $variant->is_active = $wooVariation['status'] === 'publish';
            $variant->save();

            // Sync variant inventory
            $this->syncInventory($variant, $wooVariation);

            // Sync variant barcode
            if (!empty($wooVariation['sku'])) {
                $this->syncBarcode($variant, $wooVariation['sku']);
            }
        }
    }

    /**
     * Export product variations
     */
    protected function exportVariations(Product $product): void
    {
        if (!$product->woocommerce_id) {
            return;
        }

        $variants = $product->variants;

        foreach ($variants as $variant) {
            $data = $this->mapVariantToWooCommerce($variant);

            if ($variant->woocommerce_id) {
                $this->client->put(
                    "products/{$product->woocommerce_id}/variations/{$variant->woocommerce_id}",
                    $data
                );
            } else {
                $response = $this->client->post(
                    "products/{$product->woocommerce_id}/variations",
                    $data
                );

                if ($response['success']) {
                    $variant->update([
                        'woocommerce_id' => $response['data']['id']
                    ]);
                }
            }
        }
    }

    /**
     * Sync category
     */
    protected function syncCategory(array $wooCategory): ?int
    {
        $category = ProductCategory::where('woocommerce_id', $wooCategory['id'])->first();

        if (!$category) {
            $category = ProductCategory::create([
                'woocommerce_id' => $wooCategory['id'],
                'name' => $wooCategory['name'],
                'slug' => $wooCategory['slug'],
            ]);
        }

        return $category->id;
    }

    /**
     * Sync inventory
     */
    protected function syncInventory($model, array $wooData): void
    {
        $inventory = Inventory::firstOrNew([
            'inventoriable_type' => get_class($model),
            'inventoriable_id' => $model->id,
        ]);

        $inventory->quantity = (int) ($wooData['stock_quantity'] ?? 0);
        $inventory->save();
    }

    /**
     * Sync barcode
     */
    protected function syncBarcode($model, string $code): void
    {
        Barcode::firstOrCreate([
            'barcodeable_type' => get_class($model),
            'barcodeable_id' => $model->id,
            'barcode' => $code,
        ]);
    }

    /**
     * Map local product to WooCommerce format
     */
    protected function mapProductToWooCommerce(Product $product): array
    {
        $data = [
            'name' => $product->name,
            'type' => $product->type === 'variable' ? 'variable' : 'simple',
            'status' => $product->is_active ? 'publish' : 'draft',
            'description' => $product->description,
            'sku' => $product->sku,
            'regular_price' => (string) $product->price,
            'manage_stock' => $product->track_inventory,
        ];

        if ($product->category_id) {
            $category = $product->category;
            if ($category && $category->woocommerce_id) {
                $data['categories'] = [
                    ['id' => $category->woocommerce_id]
                ];
            }
        }

        if ($product->image_url) {
            $data['images'] = [
                ['src' => $product->image_url]
            ];
        }

        if ($product->track_inventory && $product->type === 'simple') {
            $inventory = $product->inventory;
            if ($inventory) {
                $data['stock_quantity'] = $inventory->quantity;
            }
        }

        return $data;
    }

    /**
     * Map local variant to WooCommerce format
     */
    protected function mapVariantToWooCommerce(ProductVariant $variant): array
    {
        $data = [
            'sku' => $variant->sku,
            'regular_price' => (string) $variant->price,
            'manage_stock' => true,
        ];

        $inventory = $variant->inventory;
        if ($inventory) {
            $data['stock_quantity'] = $inventory->quantity;
        }

        if ($variant->attributes) {
            $attributes = json_decode($variant->attributes, true);
            $data['attributes'] = $attributes;
        }

        return $data;
    }

    /**
     * Build variant name from attributes
     */
    protected function buildVariantName(array $wooVariation): string
    {
        $attributes = $wooVariation['attributes'] ?? [];
        $parts = [];

        foreach ($attributes as $attr) {
            $parts[] = $attr['option'] ?? '';
        }

        return implode(' - ', array_filter($parts)) ?: 'Variant';
    }

    /**
     * Log sync operation
     */
    protected function logSync(
        string $type,
        string $direction,
        array $stats,
        $startTime,
        string $status = 'success',
        ?string $errorMessage = null
    ): void {
        SyncLog::create([
            'type' => $type,
            'direction' => $direction,
            'status' => $status,
            'records_processed' => $stats['created'] + $stats['updated'],
            'records_failed' => $stats['failed'],
            'error_message' => $errorMessage,
            'started_at' => $startTime,
            'completed_at' => now(),
        ]);
    }
}