<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'payments_by_status' => array_map(
                static fn (array $summary): array => [
                    'status' => $summary['status'],
                    'total_payments' => (int) $summary['total_payments'],
                    'total_amount' => (string) $summary['total_amount'],
                ],
                $this->resource['payments_by_status'] ?? [],
            ),
            'total_revenue' => (string) ($this->resource['total_revenue'] ?? '0.00'),
        ];
    }
}
