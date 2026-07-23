<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private static bool $testDatabaseMigrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) config('database.connections.mongodb.database');

        if ($database !== 'community_waste_test') {
            throw new RuntimeException(
                "Tests must use [community_waste_test], but [{$database}] is configured."
            );
        }

        /** @var Connection $connection */
        $connection = DB::connection('mongodb');

        if (! self::$testDatabaseMigrated) {
            $connection->getDatabase()->drop();
            $this->artisan('migrate', [
                '--database' => 'mongodb',
                '--force' => true,
            ])->run();

            self::$testDatabaseMigrated = true;

            return;
        }

        foreach (['users', 'households', 'wastes', 'payments'] as $collection) {
            $connection->getCollection($collection)->deleteMany([]);
        }
    }
}
