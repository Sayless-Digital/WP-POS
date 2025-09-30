<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncLogResource extends JsonResource
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
            'syncable_type' => $this->syncable_type,
            'syncable_id' => $this->syncable_id,
            'action' => $this->action,
            'status' => $this->status,
            'request_data' => $this->request_data,
            'response_data' => $this->response_data,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            
            // Computed attributes
            'action_name' => $this->action_name,
            'duration_seconds' => $this->duration_seconds,
            'formatted_duration' => $this->formatted_duration,
            'is_success' => $this->status === 'success',
            'is_failed' => $this->status === 'failed',
            
            // Relationships
            'syncable' => $this->when($this->relationLoaded('syncable'), function () {
                return match ($this->syncable_type) {
                    'App\\Models\\Product' => new ProductResource($this->syncable),
                    'App\\Models\\Order' => new OrderResource($this->syncable),
                    'App\\Models\\Customer' => new CustomerResource($this->syncable),
                    default => null,
                };
            }),
        ];
    }
}