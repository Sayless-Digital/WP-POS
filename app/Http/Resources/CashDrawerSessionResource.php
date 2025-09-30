<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashDrawerSessionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'opening_balance' => (float) $this->opening_balance,
            'closing_balance' => $this->closing_balance ? (float) $this->closing_balance : null,
            'expected_balance' => $this->expected_balance ? (float) $this->expected_balance : null,
            'difference' => $this->difference ? (float) $this->difference : null,
            'status' => $this->status,
            'opened_at' => $this->opened_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'notes' => $this->notes,
            
            // Computed attributes
            'total_cash_in' => (float) $this->total_cash_in,
            'total_cash_out' => (float) $this->total_cash_out,
            'total_cash_sales' => (float) $this->total_cash_sales,
            'duration_minutes' => $this->duration_minutes,
            'formatted_duration' => $this->formatted_duration,
            'has_discrepancy' => $this->hasDiscrepancy(),
            'is_over' => $this->isOver(),
            'is_short' => $this->isShort(),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'cash_movements' => CashMovementResource::collection($this->whenLoaded('cashMovements')),
            'orders_count' => $this->when($this->relationLoaded('orders'), fn() => $this->orders->count()),
        ];
    }
}