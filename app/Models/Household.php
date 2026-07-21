<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['owner_name', 'address', 'block', 'no'])]
class Household extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'households';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
