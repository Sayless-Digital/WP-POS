<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerGroupResource extends JsonResource
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
            'name' => $this->name,
            'discount_percentage' => (float) $this->discount_percentage,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed attributes
            'total_customers' => $this->when($this->relationLoaded('customers'), fn() => $this->total_customers),
            'total_spent' => $this->when($this->relationLoaded('customers'), fn() => (float) $this->total_spent),
            'average_spent' => $this->when($this->relationLoaded('customers'), fn() => (float) $this->average_spent),
            
            // Relationships
            'customers_count' => $this->when($this->relationLoaded('customers'), fn() => $this->customers->count()),
        ];
    }
}