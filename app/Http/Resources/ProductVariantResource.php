<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'product_id' => $this->product_id,
            'woocommerce_id' => $this->woocommerce_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'price' => (float) $this->price,
            'cost_price' => (float) $this->cost_price,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed attributes
            'formatted_attributes' => $this->formatted_attributes,
            'full_name' => $this->full_name,
            'price_with_tax' => (float) $this->price_with_tax,
            'profit_margin' => $this->when($this->cost_price, fn() => (float) $this->profit_margin),
            'stock_quantity' => $this->when($this->relationLoaded('inventory'), fn() => $this->stock_quantity),
            'available_stock' => $this->when($this->relationLoaded('inventory'), fn() => $this->available_stock),
            
            // Relationships
            'product' => new ProductResource($this->whenLoaded('product')),
            'barcodes' => BarcodeResource::collection($this->whenLoaded('barcodes')),
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
        ];
    }
}