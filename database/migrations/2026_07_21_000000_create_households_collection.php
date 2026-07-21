<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('households', function (Blueprint $collection): void {
            $collection->unique(
                ['block', 'no'],
                'households_block_no_unique',
                options: [
                    'partialFilterExpression' => [
                        'block' => ['$type' => 'string'],
                        'no' => ['$type' => 'string'],
                    ],
                ],
            );
            $collection->index('created_at', 'households_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('households');
    }
};
