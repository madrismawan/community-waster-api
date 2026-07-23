<?php

use App\Contract\Repositories\PaymentRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\InvalidArgumentException as MongoInvalidArgumentException;

afterEach(function (): void {
    Carbon::setTestNow();
    Mockery::close();
});

it('paginates payments with default pagination values', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $paginator = Mockery::mock(LengthAwarePaginator::class);

    $repository->shouldReceive('paginateFiltered')
        ->once()
        ->with(15, [], 1)
        ->andReturn($paginator);

    $service = new PaymentService($repository);

    expect($service->paginate())->toBe($paginator);
});

it('paginates payments with requested filters and integer pagination values', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $paginator = Mockery::mock(LengthAwarePaginator::class);
    $request = [
        'status' => 'paid',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
        'per_page' => '10',
        'page' => '2',
    ];

    $repository->shouldReceive('paginateFiltered')
        ->once()
        ->with(10, $request, 2)
        ->andReturn($paginator);

    $service = new PaymentService($repository);

    expect($service->paginate($request))->toBe($paginator);
});

it('finds a payment by id', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $payment = new Payment;

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('payment-id')
        ->andReturn($payment);

    $service = new PaymentService($repository);

    expect($service->find('payment-id'))->toBe($payment);
});

it('propagates a not found exception when finding a payment', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $exception = (new ModelNotFoundException)->setModel(Payment::class, ['missing-id']);

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('missing-id')
        ->andThrow($exception);

    $service = new PaymentService($repository);

    expect(fn () => $service->find('missing-id'))
        ->toThrow(ModelNotFoundException::class);
});

it('creates a pending payment due one week later', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $createdPayment = new Payment;
    $householdId = '507f1f77bcf86cd799439011';
    $now = Carbon::parse('2026-07-23 10:15:00');
    Carbon::setTestNow($now);

    $repository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $payload) use ($householdId, $now): bool {
            return array_keys($payload) === [
                'household_id',
                'amount',
                'payment_date',
                'status',
            ]
                && $payload['household_id'] instanceof ObjectId
                && (string) $payload['household_id'] === $householdId
                && $payload['amount'] === '125000.50'
                && $payload['payment_date'] instanceof Carbon
                && $payload['payment_date']->equalTo($now->copy()->addWeek())
                && $payload['status'] === PaymentStatus::Pending;
        }))
        ->andReturn($createdPayment);

    $service = new PaymentService($repository);

    $result = $service->create([
        'household_id' => $householdId,
        'amount' => '125000.50',
        'payment_date' => '2020-01-01',
        'status' => PaymentStatus::Failed,
        'ignored' => 'must not be persisted',
    ]);

    expect($result)->toBe($createdPayment);
});

it('rejects an invalid household object id when creating a payment', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $repository->shouldNotReceive('create');
    $service = new PaymentService($repository);

    expect(fn () => $service->create([
        'household_id' => 'invalid-object-id',
        'amount' => '50000.00',
    ]))->toThrow(MongoInvalidArgumentException::class);
});

it('confirms a pending payment', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $payment = new Payment(['status' => PaymentStatus::Pending]);
    $savedPayment = new Payment(['status' => PaymentStatus::Paid]);

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('payment-id')
        ->andReturn($payment);
    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(
            fn (Payment $candidate): bool => $candidate === $payment
                && $candidate->status === PaymentStatus::Paid
        ))
        ->andReturn($savedPayment);

    $service = new PaymentService($repository);

    expect($service->confirm('payment-id'))->toBe($savedPayment)
        ->and($payment->status)->toBe(PaymentStatus::Paid);
});

it('rejects confirmation of a non-pending payment', function (PaymentStatus $status): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $payment = new Payment(['status' => $status]);

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('payment-id')
        ->andReturn($payment);
    $repository->shouldNotReceive('save');

    $service = new PaymentService($repository);

    expect(fn () => $service->confirm('payment-id'))
        ->toThrow(DomainException::class, 'Only pending payments can be confirmed.');
})->with([
    'paid' => PaymentStatus::Paid,
    'failed' => PaymentStatus::Failed,
]);

it('does not save a payment when confirmation cannot find it', function (): void {
    $repository = Mockery::mock(PaymentRepositoryInterface::class);
    $exception = (new ModelNotFoundException)->setModel(Payment::class, ['missing-id']);

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('missing-id')
        ->andThrow($exception);
    $repository->shouldNotReceive('save');

    $service = new PaymentService($repository);

    expect(fn () => $service->confirm('missing-id'))
        ->toThrow(ModelNotFoundException::class);
});
