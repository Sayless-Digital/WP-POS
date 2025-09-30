<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => (int) $this->quantity,
            'price' => (float) $this->price,
            'tax_rate' => (float) $this->tax_rate,
            'discount_amount' => (float) $this->discount_amount,
            'subtotal' => (float) $this->subtotal,
            'total' => (float) $this->total,
            'created_at' => $this->created_at?->toISOString(),
            
            // Computed attributes
            'tax_amount' => (float) $this->tax_amount,
            'line_total' => (float) $this->line_total,
            'unit_price_after_discount' => (float) $this->unit_price_after_discount,
            'discount_percentage' => (float) $this->discount_percentage,
            
            // Relationships
            'order' => new OrderResource($this->whenLoaded('order')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
        ];
    }
}