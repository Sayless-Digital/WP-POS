<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Roles and permissions (if using Spatie)
            'roles' => $this->when($this->relationLoaded('roles'), fn() => $this->roles->pluck('name')),
            'permissions' => $this->when($this->relationLoaded('permissions'), fn() => $this->permissions->pluck('name')),
            
            // POS specific
            'has_open_cash_drawer' => $this->hasOpenCashDrawer(),
            'current_cash_drawer_session' => new CashDrawerSessionResource($this->whenLoaded('currentCashDrawerSession')),
            
            // Relationships counts
            'orders_count' => $this->when($this->relationLoaded('orders'), fn() => $this->orders->count()),
            'cash_drawer_sessions_count' => $this->when($this->relationLoaded('cashDrawerSessions'), fn() => $this->cashDrawerSessions->count()),
        ];
    }
}