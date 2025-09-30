<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'inventory_id' => $this->inventory_id,
            'type' => $this->type,
            'quantity_before' => (int) $this->quantity_before,
            'quantity_after' => (int) $this->quantity_after,
            'quantity_difference' => (int) $this->quantity_difference,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            
            // Relationships
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}