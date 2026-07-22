<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('payments', function (Blueprint $collection): void {
            $collection->index('household_id', 'payments_household_id_index');
            $collection->index(['status', 'payment_date'], 'payments_status_payment_date_index');
            $collection->jsonSchema([
                'bsonType' => 'object',
                'required' => [
                    'household_id',
                    'amount',
                    'payment_date',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'properties' => [
                    '_id' => ['bsonType' => 'objectId'],
                    'household_id' => ['bsonType' => 'objectId'],
                    'amount' => ['bsonType' => 'decimal'],
                    'payment_date' => ['bsonType' => 'date'],
                    'status' => ['bsonType' => 'string'],
                    'created_at' => ['bsonType' => 'date'],
                    'updated_at' => ['bsonType' => 'date'],
                ],
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('payments');
    }
};
