<?php

namespace App\Http\Resources;

use App\Enums\WasteType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WasteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'household_id' => (string) $this->household_id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'pickup_date' => $this->pickup_date?->toISOString(),
            'safety_check' => $this->when(
                $this->type === WasteType::Electronic,
                (bool) $this->safety_check,
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
