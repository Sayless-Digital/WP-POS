<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'is_synced' => (bool) $this->is_synced,
            'synced_at' => $this->synced_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed attributes
            'total_paid' => (float) $this->total_paid,
            'total_refunded' => (float) $this->total_refunded,
            'remaining_balance' => (float) $this->remaining_balance,
            
            // Relationships
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'user' => new UserResource($this->whenLoaded('user')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'refunds' => RefundResource::collection($this->whenLoaded('refunds')),
        ];
    }
}