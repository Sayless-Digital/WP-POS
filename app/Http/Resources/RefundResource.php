<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
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
            'user_id' => $this->user_id,
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'refund_method' => $this->refund_method,
            'created_at' => $this->created_at?->toISOString(),
            
            // Computed attributes
            'refund_percentage' => (float) $this->refund_percentage,
            'is_full_refund' => $this->isFullRefund(),
            'is_partial_refund' => $this->isPartialRefund(),
            
            // Relationships
            'order' => new OrderResource($this->whenLoaded('order')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}