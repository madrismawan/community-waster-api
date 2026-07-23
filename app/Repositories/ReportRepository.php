<?php

namespace App\Repositories;

use App\Contract\Repositories\ReportRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\Waste;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection as MongoCollection;

class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        private Waste $waste,
        private Payment $payment,
        private Household $household,
    ) {}

    public function wasteSummary(): array
    {
        return $this->waste->newQuery()->raw(
            fn (MongoCollection $collection): array => $collection->aggregate([
                [
                    '$group' => [
                        '_id' => [
                            'type' => '$type',
                            'status' => '$status',
                        ],
                        'total_pickups' => ['$sum' => 1],
                    ],
                ],
                [
                    '$sort' => [
                        '_id.type' => 1,
                        '_id.status' => 1,
                    ],
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'type' => '$_id.type',
                        'status' => '$_id.status',
                        'total_pickups' => 1,
                    ],
                ],
            ], [
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array',
                ],
            ])->toArray(),
        );
    }

    public function paymentSummary(): array
    {
        $results = $this->payment->newQuery()->raw(
            fn (MongoCollection $collection): array => $collection->aggregate([
                [
                    '$facet' => [
                        'by_status' => [
                            [
                                '$group' => [
                                    '_id' => '$status',
                                    'total_payments' => ['$sum' => 1],
                                    'total_amount' => ['$sum' => '$amount'],
                                ],
                            ],
                            ['$sort' => ['_id' => 1]],
                            [
                                '$project' => [
                                    '_id' => 0,
                                    'status' => '$_id',
                                    'total_payments' => 1,
                                    'total_amount' => ['$toString' => '$total_amount'],
                                ],
                            ],
                        ],
                        'revenue' => [
                            ['$match' => ['status' => PaymentStatus::Paid->value]],
                            [
                                '$group' => [
                                    '_id' => null,
                                    'total_revenue' => ['$sum' => '$amount'],
                                ],
                            ],
                            [
                                '$project' => [
                                    '_id' => 0,
                                    'total_revenue' => ['$toString' => '$total_revenue'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'payments_by_status' => '$by_status',
                        'total_revenue' => [
                            '$ifNull' => [
                                ['$arrayElemAt' => ['$revenue.total_revenue', 0]],
                                '0.00',
                            ],
                        ],
                    ],
                ],
            ], [
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array',
                ],
            ])->toArray(),
        );

        return $results[0] ?? [
            'payments_by_status' => [],
            'total_revenue' => '0.00',
        ];
    }

    public function householdHistory(string $householdId): ?array
    {
        if (strlen($householdId) !== 24 || ! ctype_xdigit($householdId)) {
            return null;
        }

        $results = $this->household->newQuery()->raw(
            fn (MongoCollection $collection): array => $collection->aggregate([
                ['$match' => ['_id' => new ObjectId($householdId)]],
                [
                    '$lookup' => [
                        'from' => 'wastes',
                        'let' => ['household_id' => '$_id'],
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => ['$eq' => ['$household_id', '$$household_id']],
                                ],
                            ],
                            ['$sort' => ['created_at' => -1]],
                            [
                                '$project' => [
                                    '_id' => 0,
                                    'id' => ['$toString' => '$_id'],
                                    'type' => 1,
                                    'status' => 1,
                                    'pickup_date' => [
                                        '$dateToString' => [
                                            'date' => '$pickup_date',
                                            'format' => '%Y-%m-%dT%H:%M:%S.%LZ',
                                            'onNull' => null,
                                        ],
                                    ],
                                    'safety_check' => 1,
                                    'created_at' => [
                                        '$dateToString' => [
                                            'date' => '$created_at',
                                            'format' => '%Y-%m-%dT%H:%M:%S.%LZ',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'as' => 'pickups',
                    ],
                ],
                [
                    '$lookup' => [
                        'from' => 'payments',
                        'let' => ['household_id' => '$_id'],
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => ['$eq' => ['$household_id', '$$household_id']],
                                ],
                            ],
                            ['$sort' => ['created_at' => -1]],
                            [
                                '$project' => [
                                    '_id' => 0,
                                    'id' => ['$toString' => '$_id'],
                                    'amount' => ['$toString' => '$amount'],
                                    'payment_date' => [
                                        '$dateToString' => [
                                            'date' => '$payment_date',
                                            'format' => '%Y-%m-%dT%H:%M:%S.%LZ',
                                        ],
                                    ],
                                    'status' => 1,
                                    'created_at' => [
                                        '$dateToString' => [
                                            'date' => '$created_at',
                                            'format' => '%Y-%m-%dT%H:%M:%S.%LZ',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'as' => 'payments',
                    ],
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'id' => ['$toString' => '$_id'],
                        'owner_name' => 1,
                        'address' => 1,
                        'block' => 1,
                        'no' => 1,
                        'pickups' => 1,
                        'payments' => 1,
                    ],
                ],
            ], [
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array',
                ],
            ])->toArray(),
        );

        return $results[0] ?? null;
    }
}
