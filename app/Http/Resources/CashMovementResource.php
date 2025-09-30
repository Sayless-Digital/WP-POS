<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashMovementResource extends JsonResource
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
            'session_id' => $this->session_id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            
            // Computed attributes
            'type_name' => $this->type_name,
            'reason_name' => $this->reason_name,
            'signed_amount' => (float) $this->signed_amount,
            'is_cash_in' => $this->type === 'cash_in',
            'is_cash_out' => $this->type === 'cash_out',
            
            // Relationships
            'cash_drawer_session' => new CashDrawerSessionResource($this->whenLoaded('cashDrawerSession')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}