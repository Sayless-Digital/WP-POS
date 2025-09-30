<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Barcode;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Create a new product with optional variants
     *
     * @param array $data
     * @return Product
     * @throws \Exception
     */
    public function createProduct(array $data): Product
    {
        DB::beginTransaction();

        try {
            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku($data['name']);
            }

            // Create the product
            $product = Product::create([
                'sku' => $data['sku'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'simple',
                'price' => $data['price'],
                'cost_price' => $data['cost_price'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? config('pos.tax_rate', 0),
                'is_active' => $data['is_active'] ?? true,
                'track_inventory' => $data['track_inventory'] ?? true,
                'image_url' => $data['image_url'] ?? null,
            ]);

            // Create inventory record if tracking inventory
            if ($product->track_inventory) {
                Inventory::create([
                    'inventoriable_type' => Product::class,
                    'inventoriable_id' => $product->id,
                    'quantity' => $data['initial_quantity'] ?? 0,
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? config('pos.low_stock_threshold', 10),
                ]);
            }

            // Create barcode if provided
            if (!empty($data['barcode'])) {
                Barcode::create([
                    'barcodeable_type' => Product::class,
                    'barcodeable_id' => $product->id,
                    'barcode' => $data['barcode'],
                    'type' => $data['barcode_type'] ?? 'EAN13',
                ]);
            }

            // Create variants if product type is variable
            if ($product->type === 'variable' && !empty($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $this->createVariant($product, $variantData);
                }
            }

            DB::commit();

            return $product->load(['category', 'inventory', 'barcodes', 'variants']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing product
     *
     * @param Product $product
     * @param array $data
     * @return Product
     * @throws \Exception
     */
    public function updateProduct(Product $product, array $data): Product
    {
        DB::beginTransaction();

        try {
            $product->update([
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'price' => $data['price'] ?? $product->price,
                'cost_price' => $data['cost_price'] ?? $product->cost_price,
                'category_id' => $data['category_id'] ?? $product->category_id,
                'tax_rate' => $data['tax_rate'] ?? $product->tax_rate,
                'is_active' => $data['is_active'] ?? $product->is_active,
                'track_inventory' => $data['track_inventory'] ?? $product->track_inventory,
                'image_url' => $data['image_url'] ?? $product->image_url,
            ]);

            // Update inventory threshold if provided
            if (isset($data['low_stock_threshold']) && $product->inventory) {
                $product->inventory->update([
                    'low_stock_threshold' => $data['low_stock_threshold'],
                ]);
            }

            DB::commit();

            return $product->fresh(['category', 'inventory', 'barcodes', 'variants']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a product variant
     *
     * @param Product $product
     * @param array $data
     * @return ProductVariant
     * @throws \Exception
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        DB::beginTransaction();

        try {
            // Generate variant SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $product->sku . '-' . Str::random(4);
            }

            $variant = $product->variants()->create([
                'sku' => $data['sku'],
                'name' => $data['name'],
                'attributes' => $data['attributes'] ?? null,
                'price' => $data['price'],
                'cost_price' => $data['cost_price'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create inventory for variant if product tracks inventory
            if ($product->track_inventory) {
                Inventory::create([
                    'inventoriable_type' => ProductVariant::class,
                    'inventoriable_id' => $variant->id,
                    'quantity' => $data['initial_quantity'] ?? 0,
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? config('pos.low_stock_threshold', 10),
                ]);
            }

            // Create barcode for variant if provided
            if (!empty($data['barcode'])) {
                Barcode::create([
                    'barcodeable_type' => ProductVariant::class,
                    'barcodeable_id' => $variant->id,
                    'barcode' => $data['barcode'],
                    'type' => $data['barcode_type'] ?? 'EAN13',
                ]);
            }

            DB::commit();

            return $variant->load(['inventory', 'barcodes']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Search products by various criteria
     *
     * @param string $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchProducts(string $query, array $filters = [])
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $products->where('category_id', $filters['category_id']);
        }

        // Apply price range filter
        if (!empty($filters['min_price'])) {
            $products->where('price', '>=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $products->where('price', '<=', $filters['max_price']);
        }

        // Apply stock filter
        if (!empty($filters['in_stock'])) {
            $products->whereHas('inventory', function ($q) {
                $q->where('quantity', '>', 0);
            });
        }

        return $products
            ->with(['category', 'inventory', 'barcodes'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Find product by barcode
     *
     * @param string $barcode
     * @return Product|ProductVariant|null
     */
    public function findByBarcode(string $barcode)
    {
        $barcodeRecord = Barcode::where('barcode', $barcode)->first();

        if (!$barcodeRecord) {
            return null;
        }

        return $barcodeRecord->barcodeable;
    }

    /**
     * Get low stock products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts()
    {
        return Product::whereHas('inventory', function ($query) {
            $query->whereRaw('quantity <= low_stock_threshold');
        })
            ->with(['inventory', 'category'])
            ->get();
    }

    /**
     * Check if product is in stock
     *
     * @param Product|ProductVariant $item
     * @param int $quantity
     * @return bool
     */
    public function isInStock($item, int $quantity = 1): bool
    {
        if (!$item->track_inventory) {
            return true;
        }

        $inventory = $item->inventory;
        if (!$inventory) {
            return false;
        }

        $available = $inventory->quantity - $inventory->reserved_quantity;
        return $available >= $quantity;
    }

    /**
     * Get product stock level
     *
     * @param Product|ProductVariant $item
     * @return int
     */
    public function getStockLevel($item): int
    {
        if (!$item->track_inventory || !$item->inventory) {
            return 0;
        }

        return $item->inventory->quantity - $item->inventory->reserved_quantity;
    }

    /**
     * Generate a unique SKU
     *
     * @param string $name
     * @return string
     */
    protected function generateSku(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        $random = strtoupper(Str::random(4));
        $sku = $base . '-' . $random;

        // Ensure uniqueness
        while (Product::where('sku', $sku)->exists() || ProductVariant::where('sku', $sku)->exists()) {
            $random = strtoupper(Str::random(4));
            $sku = $base . '-' . $random;
        }

        return $sku;
    }

    /**
     * Bulk update product prices
     *
     * @param array $productIds
     * @param float $percentage
     * @param string $type 'increase' or 'decrease'
     * @return int Number of products updated
     */
    public function bulkUpdatePrices(array $productIds, float $percentage, string $type = 'increase'): int
    {
        $multiplier = $type === 'increase' ? (1 + $percentage / 100) : (1 - $percentage / 100);

        return Product::whereIn('id', $productIds)
            ->update([
                'price' => DB::raw("price * {$multiplier}"),
            ]);
    }

    /**
     * Duplicate a product
     *
     * @param Product $product
     * @param array $overrides
     * @return Product
     */
    public function duplicateProduct(Product $product, array $overrides = []): Product
    {
        $data = array_merge([
            'name' => $product->name . ' (Copy)',
            'description' => $product->description,
            'type' => $product->type,
            'price' => $product->price,
            'cost_price' => $product->cost_price,
            'category_id' => $product->category_id,
            'tax_rate' => $product->tax_rate,
            'is_active' => false, // Duplicates start inactive
            'track_inventory' => $product->track_inventory,
            'initial_quantity' => 0,
        ], $overrides);

        return $this->createProduct($data);
    }
}