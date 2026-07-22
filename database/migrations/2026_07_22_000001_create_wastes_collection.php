<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('wastes', function (Blueprint $collection): void {
            $collection->index('household_id', 'wastes_household_id_index');
            $collection->index(['type', 'status', 'created_at'], 'wastes_type_status_created_at_index');
            $collection->index('pickup_date', 'wastes_pickup_date_index');
            $collection->jsonSchema([
                'bsonType' => 'object',
                'required' => ['household_id', 'type', 'status', 'created_at', 'updated_at'],
                'properties' => [
                    '_id' => ['bsonType' => 'objectId'],
                    'household_id' => ['bsonType' => 'objectId'],
                    'type' => ['bsonType' => 'string'],
                    'status' => ['bsonType' => 'string'],
                    'pickup_date' => ['bsonType' => ['date', 'null']],
                    'safety_check' => ['bsonType' => ['bool', 'null']],
                    'created_at' => ['bsonType' => 'date'],
                    'updated_at' => ['bsonType' => 'date'],
                ],
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('wastes');
    }
};
