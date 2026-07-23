<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    Carbon::setTestNow('2026-07-23 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('payment endpoints require authentication', function (string $method, string $uri, array $payload = []): void {
    $this->json($method, $uri, $payload)
        ->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
            'errors' => null,
        ]);
})->with([
    'index' => ['GET', '/api/payments'],
    'store' => ['POST', '/api/payments', ['household_id' => str_repeat('a', 24), 'amount' => 50000]],
    'confirm' => ['PUT', '/api/payments/'.str_repeat('a', 24).'/confirm'],
]);

test('authenticated users can list payments with household status and date filters', function (): void {
    $firstHousehold = testCreateHousehold();
    $secondHousehold = testCreateHousehold(['owner_name' => 'Second Owner']);

    $matchingPayment = testCreatePayment($firstHousehold, [
        'amount' => '75000.50',
        'status' => PaymentStatus::Paid,
        'payment_date' => Carbon::parse('2026-07-15 10:00:00'),
    ]);
    testCreatePayment($firstHousehold, [
        'status' => PaymentStatus::Pending,
        'payment_date' => Carbon::parse('2026-07-15 10:00:00'),
    ]);
    testCreatePayment($firstHousehold, [
        'status' => PaymentStatus::Paid,
        'payment_date' => Carbon::parse('2026-06-30 10:00:00'),
    ]);
    testCreatePayment($secondHousehold, [
        'status' => PaymentStatus::Paid,
        'payment_date' => Carbon::parse('2026-07-15 10:00:00'),
    ]);

    $query = http_build_query([
        'household_id' => (string) $firstHousehold->getKey(),
        'status' => PaymentStatus::Paid->value,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
        'per_page' => 10,
        'page' => 1,
    ]);

    $this->getJson('/api/payments?'.$query, testAuthHeaders())
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Payments retrieved successfully.',
            'data' => [
                [
                    'id' => (string) $matchingPayment->getKey(),
                    'household_id' => (string) $firstHousehold->getKey(),
                    'amount' => '75000.50',
                    'status' => 'paid',
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

test('payment index validates filters and requires both range boundaries', function (): void {
    $this->getJson('/api/payments?household_id=invalid&status=unknown&start_date=2026-07-25&end_date=2026-07-24', testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['household_id', 'status', 'end_date']);

    $this->getJson('/api/payments?start_date=2026-07-01', testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date']);

    $this->getJson('/api/payments?end_date=2026-07-31', testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date']);
});

test('authenticated users can create a pending payment due in one week', function (): void {
    $household = testCreateHousehold();

    $response = $this->postJson('/api/payments', [
        'household_id' => (string) $household->getKey(),
        'amount' => 75000.50,
    ], testAuthHeaders());

    $response
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Payment created successfully.',
            'data' => [
                'household_id' => (string) $household->getKey(),
                'amount' => '75000.50',
                'status' => 'pending',
            ],
            'errors' => null,
        ])
        ->assertJsonPath('data.id', fn (mixed $id): bool => is_string($id) && strlen($id) === 24)
        ->assertJsonPath(
            'data.payment_date',
            fn (mixed $date): bool => is_string($date) && str_starts_with($date, '2026-07-30T12:00:00'),
        );

    $payment = Payment::query()->firstOrFail();

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->payment_date->equalTo(now()->addWeek()))->toBeTrue();
});

test('payment creation validates amount and household', function (): void {
    $this->postJson('/api/payments', [], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['household_id', 'amount']);

    $this->postJson('/api/payments', [
        'household_id' => str_repeat('f', 24),
        'amount' => 50000,
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['household_id']);
});

test('payment amount must be greater than zero', function (int|float $amount): void {
    $household = testCreateHousehold();

    $this->postJson('/api/payments', [
        'household_id' => (string) $household->getKey(),
        'amount' => $amount,
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
})->with([
    'zero' => 0,
    'negative' => -10.00,
]);

test('a payment cannot be created for a soft-deleted household', function (): void {
    $household = testCreateHousehold();
    $household->delete();

    $this->postJson('/api/payments', [
        'household_id' => (string) $household->getKey(),
        'amount' => 50000,
    ], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['household_id']);
});

test('a pending payment can be confirmed', function (): void {
    $household = testCreateHousehold();
    $payment = testCreatePayment($household, ['status' => PaymentStatus::Pending]);

    $this->putJson('/api/payments/'.$payment->getKey().'/confirm', [], testAuthHeaders())
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Payment confirmed successfully.',
            'data' => [
                'id' => (string) $payment->getKey(),
                'status' => 'paid',
            ],
            'errors' => null,
        ]);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Paid);
});

test('only pending payments can be confirmed', function (PaymentStatus $status): void {
    $household = testCreateHousehold();
    $payment = testCreatePayment($household, ['status' => $status]);

    $this->putJson('/api/payments/'.$payment->getKey().'/confirm', [], testAuthHeaders())
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'message' => 'Only pending payments can be confirmed.',
            'errors' => null,
        ]);

    expect($payment->refresh()->status)->toBe($status);
})->with([
    'paid' => PaymentStatus::Paid,
    'failed' => PaymentStatus::Failed,
]);

test('confirming an unknown payment returns not found', function (): void {
    $this->putJson('/api/payments/'.str_repeat('f', 24).'/confirm', [], testAuthHeaders())
        ->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found.',
        ]);
});
