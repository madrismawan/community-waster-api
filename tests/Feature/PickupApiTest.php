<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;

beforeEach(function (): void {
    Carbon::setTestNow('2026-07-23 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('pickup endpoints require authentication', function (string $method, string $uri, array $payload = []): void {
    $this->json($method, $uri, $payload)
        ->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
            'errors' => null,
        ]);
})->with([
    'index' => ['GET', '/api/pickups'],
    'store' => ['POST', '/api/pickups', ['household_id' => str_repeat('a', 24), 'type' => 'organic']],
    'schedule' => ['PUT', '/api/pickups/'.str_repeat('a', 24).'/schedule', ['pickup_date' => '2026-07-24']],
    'complete' => ['PUT', '/api/pickups/'.str_repeat('a', 24).'/complete'],
    'cancel' => ['PUT', '/api/pickups/'.str_repeat('a', 24).'/cancel'],
]);

test('authenticated users can list and filter pickups', function (): void {
    $firstHousehold = testCreateHousehold();
    $secondHousehold = testCreateHousehold(['owner_name' => 'Second Owner']);

    $matchingPickup = testCreateWaste($firstHousehold, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Pending,
    ]);
    testCreateWaste($firstHousehold, [
        'type' => WasteType::Plastic,
        'status' => WasteStatus::Canceled,
    ]);
    testCreateWaste($secondHousehold, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Pending,
    ]);

    $query = http_build_query([
        'household_id' => (string) $firstHousehold->getKey(),
        'type' => WasteType::Organic->value,
        'status' => WasteStatus::Pending->value,
        'per_page' => 10,
        'page' => 1,
    ]);

    $this->getJson('/api/pickups?'.$query, testAuthHeaders())
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Pickups retrieved successfully.',
            'data' => [
                [
                    'id' => (string) $matchingPickup->getKey(),
                    'household_id' => (string) $firstHousehold->getKey(),
                    'type' => 'organic',
                    'status' => 'pending',
                    'pickup_date' => null,
                ],
            ],
            'meta' => [
                'current_page' => 1,
                'per_page' => 10,
                'total' => 1,
            ],
            'errors' => null,
        ])
        ->assertJsonCount(1, 'data');
});

test('pickup index rejects invalid filters', function (): void {
    $this->getJson('/api/pickups?type=metal&status=unknown&household_id=invalid', testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'status', 'household_id'])
        ->assertJsonPath('success', false);
});

test('authenticated users can create a pending pickup', function (): void {
    $household = testCreateHousehold();

    $response = $this->postJson('/api/pickups', [
        'household_id' => (string) $household->getKey(),
        'type' => WasteType::Paper->value,
    ], testAuthHeaders());

    $response
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Pickup created successfully.',
            'data' => [
                'household_id' => (string) $household->getKey(),
                'type' => 'paper',
                'status' => 'pending',
                'pickup_date' => null,
            ],
            'errors' => null,
        ])
        ->assertJsonPath('data.id', fn (mixed $id): bool => is_string($id) && strlen($id) === 24);

    $this->assertDatabaseHas('wastes', [
        'household_id' => new ObjectId((string) $household->getKey()),
        'type' => 'paper',
        'status' => 'pending',
    ], 'mongodb');
});

test('pickup creation validates its payload and household', function (): void {
    $this->postJson('/api/pickups', [], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['household_id', 'type']);

    $this->postJson('/api/pickups', [
        'household_id' => str_repeat('f', 24),
        'type' => 'organic',
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['household_id']);
});

test('a pickup cannot be created for a soft-deleted household', function (): void {
    $household = testCreateHousehold();
    $household->delete();

    $this->postJson('/api/pickups', [
        'household_id' => (string) $household->getKey(),
        'type' => WasteType::Organic->value,
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['household_id']);
});

test('a pickup cannot be created while the household has a pending payment', function (): void {
    $household = testCreateHousehold();
    testCreatePayment($household, ['status' => PaymentStatus::Pending]);

    $this->postJson('/api/pickups', [
        'household_id' => (string) $household->getKey(),
        'type' => 'organic',
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'message' => 'Cannot create a waste pickup because the household has an unpaid payment.',
            'errors' => null,
        ]);
});

test('a pending pickup can be scheduled', function (): void {
    $household = testCreateHousehold();
    $pickup = testCreateWaste($household, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Pending,
    ]);

    $this->putJson('/api/pickups/'.$pickup->getKey().'/schedule', [
        'pickup_date' => '2026-07-25',
    ], testAuthHeaders())
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Pickup scheduled successfully.',
            'data' => [
                'id' => (string) $pickup->getKey(),
                'status' => 'scheduled',
            ],
            'errors' => null,
        ])
        ->assertJsonPath(
            'data.pickup_date',
            fn (mixed $date): bool => is_string($date) && str_starts_with($date, '2026-07-25T00:00:00'),
        );
});

test('electronic pickups require a safety check before scheduling', function (): void {
    $household = testCreateHousehold();
    $pickup = testCreateWaste($household, [
        'type' => WasteType::Electronic,
        'status' => WasteStatus::Pending,
    ]);

    $this->putJson('/api/pickups/'.$pickup->getKey().'/schedule', [
        'pickup_date' => '2026-07-25',
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'message' => 'Electronic waste requires a safety check before scheduling.',
        ]);

    $this->putJson('/api/pickups/'.$pickup->getKey().'/schedule', [
        'pickup_date' => '2026-07-25',
        'safety_check' => true,
    ], testAuthHeaders())
        ->assertOk()
        ->assertJsonPath('data.status', 'scheduled')
        ->assertJsonPath('data.safety_check', true);
});

test('pickup scheduling validates the date and lifecycle state', function (): void {
    $household = testCreateHousehold();
    $pendingPickup = testCreateWaste($household);

    $this->putJson('/api/pickups/'.$pendingPickup->getKey().'/schedule', [
        'pickup_date' => '2026-07-22',
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pickup_date']);

    $scheduledPickup = testCreateWaste($household, [
        'status' => WasteStatus::Scheduled,
        'pickup_date' => now()->addDay(),
    ]);

    $this->putJson('/api/pickups/'.$scheduledPickup->getKey().'/schedule', [
        'pickup_date' => '2026-07-25',
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Only pending pickups can be scheduled.');
});

test('a scheduled pickup can be completed and creates a pending payment due in one week', function (): void {
    $household = testCreateHousehold();
    $pickup = testCreateWaste($household, [
        'type' => WasteType::Electronic,
        'status' => WasteStatus::Scheduled,
        'pickup_date' => now()->addDay(),
        'safety_check' => true,
    ]);

    $this->putJson('/api/pickups/'.$pickup->getKey().'/complete', [], testAuthHeaders())
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Pickup completed successfully.',
            'data' => [
                'id' => (string) $pickup->getKey(),
                'status' => 'completed',
            ],
            'errors' => null,
        ]);

    $payment = Payment::query()
        ->where('household_id', new ObjectId((string) $household->getKey()))
        ->first();

    expect($payment)
        ->not->toBeNull()
        ->and($payment->amount)->toBe('100000.00')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->payment_date->equalTo(now()->addWeek()))->toBeTrue();
});

test('only scheduled pickups can be completed', function (): void {
    $household = testCreateHousehold();
    $pickup = testCreateWaste($household, ['status' => WasteStatus::Pending]);

    $this->putJson('/api/pickups/'.$pickup->getKey().'/complete', [], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Only scheduled pickups can be completed.');

    expect(Payment::query()->count())->toBe(0);
});

test('pending and scheduled pickups can be canceled', function (WasteStatus $status): void {
    $household = testCreateHousehold();
    $pickup = testCreateWaste($household, [
        'status' => $status,
        'pickup_date' => $status === WasteStatus::Scheduled ? now()->addDay() : null,
    ]);

    $this->putJson('/api/pickups/'.$pickup->getKey().'/cancel', [], testAuthHeaders())
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Pickup canceled successfully.',
            'data' => [
                'id' => (string) $pickup->getKey(),
                'status' => 'canceled',
            ],
        ]);
})->with([
    'pending' => WasteStatus::Pending,
    'scheduled' => WasteStatus::Scheduled,
]);

test('completed and canceled pickups cannot be canceled', function (WasteStatus $status): void {
    $household = testCreateHousehold();
    $pickup = testCreateWaste($household, ['status' => $status]);

    $this->putJson('/api/pickups/'.$pickup->getKey().'/cancel', [], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Only pending or scheduled pickups can be canceled.');
})->with([
    'completed' => WasteStatus::Completed,
    'canceled' => WasteStatus::Canceled,
]);

test('pickup mutations return not found for an unknown pickup', function (string $action, array $payload): void {
    $this->putJson('/api/pickups/'.str_repeat('f', 24).'/'.$action, $payload, testAuthHeaders())
        ->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
        ]);
})->with([
    'schedule' => ['schedule', ['pickup_date' => '2026-07-25']],
    'complete' => ['complete', []],
    'cancel' => ['cancel', []],
]);
