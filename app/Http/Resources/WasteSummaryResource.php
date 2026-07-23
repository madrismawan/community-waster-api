<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WasteSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource['type'],
            'status' => $this->resource['status'],
            'total_pickups' => (int) $this->resource['total_pickups'],
        ];
    }
}
