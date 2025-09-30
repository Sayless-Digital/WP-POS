<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventoriable_type' => $this->inventoriable_type,
            'inventoriable_id' => $this->inventoriable_id,
            'quantity' => (int) $this->quantity,
            'reserved_quantity' => (int) $this->reserved_quantity,
            'available_quantity' => (int) $this->available_quantity,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
            'last_counted_at' => $this->last_counted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Status flags
            'is_low_stock' => $this->quantity <= $this->low_stock_threshold,
            'is_out_of_stock' => $this->quantity <= 0,
            'is_in_stock' => $this->quantity > 0,
            
            // Relationships
            'inventoriable' => $this->when($this->relationLoaded('inventoriable'), function () {
                return match ($this->inventoriable_type) {
                    'App\\Models\\Product' => new ProductResource($this->inventoriable),
                    'App\\Models\\ProductVariant' => new ProductVariantResource($this->inventoriable),
                    default => null,
                };
            }),
            'stock_movements' => StockMovementResource::collection($this->whenLoaded('stockMovements')),
        ];
    }
}