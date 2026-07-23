<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('users', function (Blueprint $collection): void {
            $collection->unique('email', 'users_email_unique');
            $collection->jsonSchema([
                'bsonType' => 'object',
                'required' => ['name', 'email', 'password', 'created_at', 'updated_at'],
                'properties' => [
                    '_id' => ['bsonType' => 'objectId'],
                    'name' => ['bsonType' => 'string'],
                    'email' => ['bsonType' => 'string'],
                    'password' => ['bsonType' => 'string'],
                    'remember_token' => ['bsonType' => ['string', 'null']],
                    'created_at' => ['bsonType' => 'date'],
                    'updated_at' => ['bsonType' => 'date'],
                ],
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('users');
    }
};
