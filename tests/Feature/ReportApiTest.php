<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    Carbon::setTestNow('2026-07-23 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('waste summary aggregates pickup totals by type and status', function (): void {
    $household = testCreateHousehold();

    testCreateWaste($household, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Pending,
    ]);
    testCreateWaste($household, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Pending,
    ]);
    testCreateWaste($household, [
        'type' => WasteType::Plastic,
        'status' => WasteStatus::Completed,
        'pickup_date' => now()->subDay(),
    ]);

    $this->getJson('/api/reports/waste-summary')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Waste summary retrieved successfully.',
            'errors' => null,
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'type' => 'organic',
            'status' => 'pending',
            'total_pickups' => 2,
        ])
        ->assertJsonFragment([
            'type' => 'plastic',
            'status' => 'completed',
            'total_pickups' => 1,
        ]);
});

test('waste summary returns an empty collection when no pickups exist', function (): void {
    $this->getJson('/api/reports/waste-summary')
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('success', true);
});

test('payment summary aggregates each status and counts only paid amounts as revenue', function (): void {
    $household = testCreateHousehold();

    testCreatePayment($household, [
        'amount' => '50000.00',
        'status' => PaymentStatus::Paid,
    ]);
    testCreatePayment($household, [
        'amount' => '75000.50',
        'status' => PaymentStatus::Paid,
    ]);
    testCreatePayment($household, [
        'amount' => '20000.00',
        'status' => PaymentStatus::Pending,
    ]);
    testCreatePayment($household, [
        'amount' => '10000.00',
        'status' => PaymentStatus::Failed,
    ]);

    $this->getJson('/api/reports/payment-summary')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Payment summary retrieved successfully.',
            'data' => [
                'total_revenue' => '125000.50',
            ],
            'errors' => null,
        ])
        ->assertJsonCount(3, 'data.payments_by_status')
        ->assertJsonFragment([
            'status' => 'paid',
            'total_payments' => 2,
            'total_amount' => '125000.50',
        ])
        ->assertJsonFragment([
            'status' => 'pending',
            'total_payments' => 1,
            'total_amount' => '20000.00',
        ])
        ->assertJsonFragment([
            'status' => 'failed',
            'total_payments' => 1,
            'total_amount' => '10000.00',
        ]);
});

test('payment summary returns zero revenue and no status groups when no payments exist', function (): void {
    $this->getJson('/api/reports/payment-summary')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'payments_by_status' => [],
                'total_revenue' => '0.00',
            ],
        ]);
});

test('household history returns its pickup and payment history', function (): void {
    $household = testCreateHousehold([
        'owner_name' => 'Ayu Lestari',
        'address' => 'Jalan Melati No. 1',
        'block' => 'A',
        'no' => '01',
    ]);
    $pickup = testCreateWaste($household, [
        'type' => WasteType::Electronic,
        'status' => WasteStatus::Scheduled,
        'pickup_date' => Carbon::parse('2026-07-25 09:00:00'),
    ]);
    $pickup->safety_check = true;
    $pickup->save();
    $payment = testCreatePayment($household, [
        'amount' => '100000.00',
        'status' => PaymentStatus::Paid,
        'payment_date' => Carbon::parse('2026-07-20 10:00:00'),
    ]);

    $this->getJson('/api/reports/households/'.$household->getKey().'/history')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Household history retrieved successfully.',
            'data' => [
                'id' => (string) $household->getKey(),
                'owner_name' => 'Ayu Lestari',
                'address' => 'Jalan Melati No. 1',
                'block' => 'A',
                'no' => '01',
                'pickups' => [
                    [
                        'id' => (string) $pickup->getKey(),
                        'type' => 'electronic',
                        'status' => 'scheduled',
                        'safety_check' => true,
                    ],
                ],
                'payments' => [
                    [
                        'id' => (string) $payment->getKey(),
                        'amount' => '100000.00',
                        'status' => 'paid',
                    ],
                ],
            ],
            'errors' => null,
        ])
        ->assertJsonCount(1, 'data.pickups')
        ->assertJsonCount(1, 'data.payments')
        ->assertJsonPath(
            'data.pickups.0.pickup_date',
            fn (mixed $date): bool => is_string($date) && str_starts_with($date, '2026-07-25T09:00:00'),
        )
        ->assertJsonPath(
            'data.payments.0.payment_date',
            fn (mixed $date): bool => is_string($date) && str_starts_with($date, '2026-07-20T10:00:00'),
        );
});

test('household history returns not found for malformed unknown and deleted households', function (string $householdId): void {
    $this->getJson('/api/reports/households/'.$householdId.'/history')
        ->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
            'errors' => null,
        ]);
})->with([
    'malformed id' => 'invalid-id',
    'unknown id' => str_repeat('f', 24),
]);

test('household history excludes soft deleted households', function (): void {
    $household = testCreateHousehold();
    testCreateWaste($household);
    testCreatePayment($household);
    $household->delete();

    $this->getJson('/api/reports/households/'.$household->getKey().'/history')
        ->assertNotFound()
        ->assertJsonPath('message', 'Resource not found.');
});
