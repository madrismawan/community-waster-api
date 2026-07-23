<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdHistoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'owner_name' => $this->resource['owner_name'],
            'address' => $this->resource['address'],
            'block' => $this->resource['block'] ?? null,
            'no' => $this->resource['no'] ?? null,
            'pickups' => array_map(
                static function (array $pickup): array {
                    $data = [
                        'id' => $pickup['id'],
                        'type' => $pickup['type'],
                        'status' => $pickup['status'],
                        'pickup_date' => $pickup['pickup_date'],
                        'created_at' => $pickup['created_at'],
                    ];

                    if (array_key_exists('safety_check', $pickup)) {
                        $data['safety_check'] = (bool) $pickup['safety_check'];
                    }

                    return $data;
                },
                $this->resource['pickups'] ?? [],
            ),
            'payments' => array_map(
                static fn (array $payment): array => [
                    'id' => $payment['id'],
                    'amount' => (string) $payment['amount'],
                    'payment_date' => $payment['payment_date'],
                    'status' => $payment['status'],
                    'created_at' => $payment['created_at'],
                ],
                $this->resource['payments'] ?? [],
            ),
        ];
    }
}
