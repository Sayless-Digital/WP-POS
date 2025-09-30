<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\InventoryResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends ApiController
{
    /**
     * Display a listing of inventory.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $lowStock = $request->input('low_stock');
        $outOfStock = $request->input('out_of_stock');
        $inStock = $request->input('in_stock');

        $query = Inventory::with(['inventoriable', 'stockMovements']);

        // Apply filters
        if ($lowStock !== null && filter_var($lowStock, FILTER_VALIDATE_BOOLEAN)) {
            $query->lowStock();
        }

        if ($outOfStock !== null && filter_var($outOfStock, FILTER_VALIDATE_BOOLEAN)) {
            $query->outOfStock();
        }

        if ($inStock !== null && filter_var($inStock, FILTER_VALIDATE_BOOLEAN)) {
            $query->inStock();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'quantity');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $inventory = $query->paginate($perPage);

        return $this->paginatedResponse($inventory, InventoryResource::class);
    }

    /**
     * Display inventory for a specific product.
     */
    public function show(string $type, string $id): JsonResponse
    {
        if (!in_array($type, ['product', 'variant'])) {
            return $this->errorResponse('Invalid inventory type. Must be "product" or "variant"');
        }

        $inventoriableType = $type === 'product' ? Product::class : ProductVariant::class;
        
        $inventory = Inventory::where('inventoriable_type', $inventoriableType)
            ->where('inventoriable_id', $id)
            ->with(['inventoriable', 'stockMovements'])
            ->first();

        if (!$inventory) {
            return $this->notFoundResponse('Inventory not found');
        }

        return $this->resourceResponse(new InventoryResource($inventory));
    }

    /**
     * Adjust inventory quantity.
     */
    public function adjust(Request $request, string $type, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer',
            'reason' => 'required|in:purchase,sale,adjustment,return,transfer,damage,theft,count',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!in_array($type, ['product', 'variant'])) {
            return $this->errorResponse('Invalid inventory type. Must be "product" or "variant"');
        }

        $inventoriableType = $type === 'product' ? Product::class : ProductVariant::class;
        
        $inventory = Inventory::where('inventoriable_type', $inventoriableType)
            ->where('inventoriable_id', $id)
            ->first();

        if (!$inventory) {
            // Create inventory if it doesn't exist
            $inventory = Inventory::create([
                'inventoriable_type' => $inventoriableType,
                'inventoriable_id' => $id,
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]);
        }

        try {
            $inventory->adjustQuantity(
                $request->quantity,
                $request->reason,
                $request->notes,
                auth()->id()
            );

            $inventory->load(['inventoriable', 'stockMovements']);

            return $this->resourceResponse(
                new InventoryResource($inventory),
                'Inventory adjusted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to adjust inventory: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Perform physical count.
     */
    public function physicalCount(Request $request, string $type, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'counted_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!in_array($type, ['product', 'variant'])) {
            return $this->errorResponse('Invalid inventory type. Must be "product" or "variant"');
        }

        $inventoriableType = $type === 'product' ? Product::class : ProductVariant::class;
        
        $inventory = Inventory::where('inventoriable_type', $inventoriableType)
            ->where('inventoriable_id', $id)
            ->first();

        if (!$inventory) {
            return $this->notFoundResponse('Inventory not found');
        }

        try {
            $inventory->physicalCount(
                $request->counted_quantity,
                $request->notes,
                auth()->id()
            );

            $inventory->load(['inventoriable', 'stockMovements']);

            return $this->resourceResponse(
                new InventoryResource($inventory),
                'Physical count completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to perform physical count: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reserve inventory.
     */
    public function reserve(Request $request, string $type, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!in_array($type, ['product', 'variant'])) {
            return $this->errorResponse('Invalid inventory type. Must be "product" or "variant"');
        }

        $inventoriableType = $type === 'product' ? Product::class : ProductVariant::class;
        
        $inventory = Inventory::where('inventoriable_type', $inventoriableType)
            ->where('inventoriable_id', $id)
            ->first();

        if (!$inventory) {
            return $this->notFoundResponse('Inventory not found');
        }

        try {
            $inventory->reserve($request->quantity);
            $inventory->load('inventoriable');

            return $this->resourceResponse(
                new InventoryResource($inventory),
                'Inventory reserved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reserve inventory: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Release reserved inventory.
     */
    public function release(Request $request, string $type, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!in_array($type, ['product', 'variant'])) {
            return $this->errorResponse('Invalid inventory type. Must be "product" or "variant"');
        }

        $inventoriableType = $type === 'product' ? Product::class : ProductVariant::class;
        
        $inventory = Inventory::where('inventoriable_type', $inventoriableType)
            ->where('inventoriable_id', $id)
            ->first();

        if (!$inventory) {
            return $this->notFoundResponse('Inventory not found');
        }

        try {
            $inventory->release($request->quantity);
            $inventory->load('inventoriable');

            return $this->resourceResponse(
                new InventoryResource($inventory),
                'Reserved inventory released successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to release inventory: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get stock movements for inventory.
     */
    public function movements(Request $request, string $type, string $id): JsonResponse
    {
        if (!in_array($type, ['product', 'variant'])) {
            return $this->errorResponse('Invalid inventory type. Must be "product" or "variant"');
        }

        $inventoriableType = $type === 'product' ? Product::class : ProductVariant::class;
        
        $inventory = Inventory::where('inventoriable_type', $inventoriableType)
            ->where('inventoriable_id', $id)
            ->first();

        if (!$inventory) {
            return $this->notFoundResponse('Inventory not found');
        }

        $perPage = $request->input('per_page', 15);
        $reason = $request->input('reason');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = $inventory->stockMovements()->with(['user']);

        if ($reason) {
            $query->byReason($reason);
        }

        if ($dateFrom && $dateTo) {
            $query->dateRange($dateFrom, $dateTo);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->paginatedResponse($movements, StockMovementResource::class);
    }

    /**
     * Get low stock items.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $inventory = Inventory::with('inventoriable')
            ->lowStock()
            ->orderBy('quantity', 'asc')
            ->paginate($perPage);

        return $this->paginatedResponse($inventory, InventoryResource::class);
    }

    /**
     * Get out of stock items.
     */
    public function outOfStock(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $inventory = Inventory::with('inventoriable')
            ->outOfStock()
            ->paginate($perPage);

        return $this->paginatedResponse($inventory, InventoryResource::class);
    }
}