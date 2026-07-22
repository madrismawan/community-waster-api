<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Casts\ObjectId;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['household_id', 'amount', 'payment_date', 'status'])]
class Payment extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'payments';

    protected $attributes = [
        'status' => 'pending',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    protected function casts(): array
    {
        return [
            'household_id' => ObjectId::class,
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
            'status' => PaymentStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
