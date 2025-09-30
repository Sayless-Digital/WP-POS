<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncQueueResource extends JsonResource
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
            'payload' => $this->payload,
            'status' => $this->status,
            'attempts' => (int) $this->attempts,
            'last_error' => $this->last_error,
            'created_at' => $this->created_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            
            // Computed attributes
            'action_name' => $this->action_name,
            'should_retry' => $this->shouldRetry(),
            
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