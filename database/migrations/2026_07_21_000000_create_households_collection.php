<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('households', function (Blueprint $collection): void {
            $collection->index('owner_name', 'households_owner_name_index');
            $collection->index(['block', 'no'], 'households_block_no_index');
            $collection->index('deleted_at', 'households_deleted_at_index');
            $collection->jsonSchema([
                'bsonType' => 'object',
                'required' => ['owner_name', 'address', 'created_at', 'updated_at'],
                'properties' => [
                    '_id' => ['bsonType' => 'objectId'],
                    'owner_name' => ['bsonType' => 'string'],
                    'address' => ['bsonType' => 'string'],
                    'block' => ['bsonType' => ['string', 'null']],
                    'no' => ['bsonType' => ['string', 'null']],
                    'deleted_at' => ['bsonType' => ['date', 'null']],
                    'created_at' => ['bsonType' => 'date'],
                    'updated_at' => ['bsonType' => 'date'],
                ],
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('households');
    }
};
