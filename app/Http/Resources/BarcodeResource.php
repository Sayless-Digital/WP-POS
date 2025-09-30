<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarcodeResource extends JsonResource
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
            'barcodeable_type' => $this->barcodeable_type,
            'barcodeable_id' => $this->barcodeable_id,
            'barcode' => $this->barcode,
            'type' => $this->type,
            'is_valid' => $this->isValidFormat(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'barcodeable' => $this->when($this->relationLoaded('barcodeable'), function () {
                return match ($this->barcodeable_type) {
                    'App\\Models\\Product' => new ProductResource($this->barcodeable),
                    'App\\Models\\ProductVariant' => new ProductVariantResource($this->barcodeable),
                    default => null,
                };
            }),
        ];
    }
}