<?php

namespace App\Models;

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Casts\ObjectId;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['household_id', 'type', 'pickup_date', 'status', 'safety_check'])]
class Waste extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'wastes';

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
            'type' => WasteType::class,
            'pickup_date' => 'datetime',
            'status' => WasteStatus::class,
            'safety_check' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
