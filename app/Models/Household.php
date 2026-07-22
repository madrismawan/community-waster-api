<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['owner_name', 'address', 'block', 'no'])]
class Household extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'households';

    public function wastes(): HasMany
    {
        return $this->hasMany(Waste::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
