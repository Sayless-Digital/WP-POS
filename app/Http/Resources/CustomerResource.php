<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'customer_group_id' => $this->customer_group_id,
            'loyalty_points' => (int) $this->loyalty_points,
            'total_spent' => (float) $this->total_spent,
            'total_orders' => (int) $this->total_orders,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'synced_at' => $this->synced_at?->toISOString(),
            
            // Computed attributes
            'full_name' => $this->full_name,
            'display_name' => $this->display_name,
            'average_order_value' => (float) $this->average_order_value,
            'discount_percentage' => (float) $this->discount_percentage,
            'is_vip' => $this->isVip(),
            'is_active' => $this->isActive(),
            
            // Relationships
            'customer_group' => new CustomerGroupResource($this->whenLoaded('customerGroup')),
            'orders_count' => $this->when($this->relationLoaded('orders'), fn() => $this->orders->count()),
        ];
    }
}