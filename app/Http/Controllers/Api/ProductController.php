<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends ApiController
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $isActive = $request->input('is_active');
        $type = $request->input('type');
        $inStock = $request->input('in_stock');

        $query = Product::with(['category', 'variants', 'inventory', 'barcodes']);

        // Apply filters
        if ($search) {
            $query->search($search);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($isActive !== null) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($inStock !== null && filter_var($inStock, FILTER_VALIDATE_BOOLEAN)) {
            $query->inStock();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($perPage);

        return $this->paginatedResponse($products, ProductResource::class);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sku' => 'required|string|max:100|unique:products,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,variable',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
            'image_url' => 'nullable|url|max:500',
            'woocommerce_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $product = Product::create($request->all());
        $product->load(['category', 'variants', 'inventory', 'barcodes']);

        return $this->resourceResponse(
            new ProductResource($product),
            'Product created successfully',
            201
        );
    }

    /**
     * Display the specified product.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['category', 'variants', 'inventory', 'barcodes'])->find($id);

        if (!$product) {
            return $this->notFoundResponse('Product not found');
        }

        return $this->resourceResponse(new ProductResource($product));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->notFoundResponse('Product not found');
        }

        $validator = Validator::make($request->all(), [
            'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:simple,variable',
            'price' => 'sometimes|required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
            'image_url' => 'nullable|url|max:500',
            'woocommerce_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $product->update($request->all());
        $product->load(['category', 'variants', 'inventory', 'barcodes']);

        return $this->resourceResponse(
            new ProductResource($product),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified product.
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->notFoundResponse('Product not found');
        }

        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }

    /**
     * Search products by barcode.
     */
    public function searchByBarcode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $barcode = $request->input('barcode');
        
        $product = Product::whereHas('barcodes', function ($query) use ($barcode) {
            $query->where('barcode', $barcode);
        })->with(['category', 'variants', 'inventory', 'barcodes'])->first();

        if (!$product) {
            return $this->notFoundResponse('Product not found with this barcode');
        }

        return $this->resourceResponse(new ProductResource($product));
    }

    /**
     * Get low stock products.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $products = Product::with(['category', 'inventory'])
            ->lowStock()
            ->paginate($perPage);

        return $this->paginatedResponse($products, ProductResource::class);
    }

    /**
     * Bulk update product status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $updated = Product::whereIn('id', $request->product_ids)
            ->update(['is_active' => $request->is_active]);

        return $this->successResponse(
            ['updated_count' => $updated],
            'Products status updated successfully'
        );
    }
}