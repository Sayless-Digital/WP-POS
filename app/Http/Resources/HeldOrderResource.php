<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeldOrderResource extends JsonResource
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
            'reference' => $this->reference,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'items' => $this->items,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed attributes
            'item_count' => $this->item_count,
            'total_quantity' => $this->total_quantity,
            'subtotal' => $this->when(isset($this->items['subtotal']), fn() => (float) $this->items['subtotal']),
            'total' => $this->when(isset($this->items['total']), fn() => (float) $this->items['total']),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
        ];
    }
}