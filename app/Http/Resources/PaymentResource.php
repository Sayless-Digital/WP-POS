<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payment_method' => $this->payment_method,
            'amount' => (float) $this->amount,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            
            // Computed attributes
            'payment_method_name' => $this->payment_method_name,
            'is_cash' => $this->isCash(),
            'is_card' => $this->isCard(),
            'is_mobile' => $this->isMobile(),
            
            // Relationships
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}