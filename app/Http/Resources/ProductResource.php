<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'woocommerce_id' => $this->woocommerce_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'price' => (float) $this->price,
            'cost_price' => (float) $this->cost_price,
            'category_id' => $this->category_id,
            'tax_rate' => (float) $this->tax_rate,
            'is_active' => (bool) $this->is_active,
            'track_inventory' => (bool) $this->track_inventory,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'synced_at' => $this->synced_at?->toISOString(),
            
            // Computed attributes
            'stock_quantity' => $this->when($this->relationLoaded('inventory'), fn() => $this->stock_quantity),
            'available_stock' => $this->when($this->relationLoaded('inventory'), fn() => $this->available_stock),
            'price_with_tax' => (float) $this->price_with_tax,
            'profit_margin' => $this->when($this->cost_price, fn() => (float) $this->profit_margin),
            
            // Relationships
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'barcodes' => BarcodeResource::collection($this->whenLoaded('barcodes')),
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
        ];
    }
}