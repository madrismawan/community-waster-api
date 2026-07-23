<?php

use App\Contract\Repositories\PaymentRepositoryInterface;
use App\Contract\Repositories\WasteRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Payment;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;
use App\Services\WasteService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\InvalidArgumentException as MongoInvalidArgumentException;

afterEach(function (): void {
    Carbon::setTestNow();
    Mockery::close();
});

it('paginates pickups with default pagination values', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $paginator = Mockery::mock(LengthAwarePaginator::class);

    $wasteRepository->shouldReceive('paginateFiltered')
        ->once()
        ->with(15, [], 1)
        ->andReturn($paginator);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect($service->paginate())->toBe($paginator);
});

it('paginates pickups with requested filters and integer pagination values', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $paginator = Mockery::mock(LengthAwarePaginator::class);
    $request = [
        'type' => 'electronic',
        'status' => 'scheduled',
        'per_page' => '20',
        'page' => '4',
    ];

    $wasteRepository->shouldReceive('paginateFiltered')
        ->once()
        ->with(20, $request, 4)
        ->andReturn($paginator);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect($service->paginate($request))->toBe($paginator);
});

it('finds a pickup by id', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteOrganic;

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect($service->find('pickup-id'))->toBe($waste);
});

it('propagates a not found exception when finding a pickup', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $exception = (new ModelNotFoundException)->setModel(Waste::class, ['missing-id']);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('missing-id')
        ->andThrow($exception);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->find('missing-id'))
        ->toThrow(ModelNotFoundException::class);
});

it('creates a pending pickup when the household has no unpaid payment', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $createdWaste = new WasteOrganic;
    $householdId = '507f1f77bcf86cd799439011';

    $paymentRepository->shouldReceive('findByFilters')
        ->once()
        ->with(Mockery::on(
            fn (array $filters): bool => $filters['household_id'] instanceof ObjectId
                && (string) $filters['household_id'] === $householdId
                && $filters['status'] === PaymentStatus::Pending->value
        ))
        ->andReturnNull();
    $wasteRepository->shouldReceive('create')
        ->once()
        ->with([
            'household_id' => $householdId,
            'type' => WasteType::Organic->value,
            'pickup_date' => null,
            'status' => WasteStatus::Pending,
        ])
        ->andReturn($createdWaste);

    $service = new WasteService($wasteRepository, $paymentRepository);

    $result = $service->create([
        'household_id' => $householdId,
        'type' => WasteType::Organic->value,
        'pickup_date' => '2020-01-01',
        'status' => WasteStatus::Completed,
        'ignored' => 'must not be persisted',
    ]);

    expect($result)->toBe($createdWaste);
});

it('rejects a pickup when the household has an unpaid payment', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $householdId = '507f1f77bcf86cd799439011';

    $paymentRepository->shouldReceive('findByFilters')
        ->once()
        ->with(Mockery::on(
            fn (array $filters): bool => $filters['household_id'] instanceof ObjectId
                && (string) $filters['household_id'] === $householdId
                && $filters['status'] === PaymentStatus::Pending->value
        ))
        ->andReturn(new Payment);
    $wasteRepository->shouldNotReceive('create');

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->create([
        'household_id' => $householdId,
        'type' => WasteType::Organic->value,
    ]))->toThrow(
        DomainException::class,
        'Cannot create a waste pickup because the household has an unpaid payment.'
    );
});

it('rejects an invalid household object id when creating a pickup', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);

    $paymentRepository->shouldNotReceive('findByFilters');
    $wasteRepository->shouldNotReceive('create');

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->create([
        'household_id' => 'invalid-object-id',
        'type' => WasteType::Organic->value,
    ]))->toThrow(MongoInvalidArgumentException::class);
});

it('schedules a pending pickup', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteOrganic([
        'type' => WasteType::Organic,
        'status' => WasteStatus::Pending,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(
            fn (Waste $candidate): bool => $candidate === $waste
                && $candidate->status === WasteStatus::Scheduled
                && $candidate->pickup_date->toDateString() === '2026-08-01'
        ))
        ->andReturn($waste);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect($service->schedule('pickup-id', [
        'pickup_date' => '2026-08-01',
    ]))->toBe($waste)
        ->and($waste->status)->toBe(WasteStatus::Scheduled);
});

it('schedules electronic waste after a safety check', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteElectronic([
        'type' => WasteType::Electronic,
        'status' => WasteStatus::Pending,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(
            fn (Waste $candidate): bool => $candidate === $waste
                && $candidate->status === WasteStatus::Scheduled
                && $candidate->pickup_date->toDateString() === '2026-08-01'
                && $candidate->safety_check === true
        ))
        ->andReturn($waste);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect($service->schedule('pickup-id', [
        'pickup_date' => '2026-08-01',
        'safety_check' => true,
    ]))->toBe($waste)
        ->and($waste->safety_check)->toBeTrue();
});

it('rejects scheduling electronic waste without a safety check', function (array $request): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteElectronic([
        'type' => WasteType::Electronic,
        'status' => WasteStatus::Pending,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldNotReceive('save');

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->schedule('pickup-id', $request))
        ->toThrow(
            DomainException::class,
            'Electronic waste requires a safety check before scheduling.'
        );
})->with([
    'missing safety check' => [[
        'pickup_date' => '2026-08-01',
    ]],
    'failed safety check' => [[
        'pickup_date' => '2026-08-01',
        'safety_check' => false,
    ]],
]);

it('rejects scheduling a pickup that is not pending', function (WasteStatus $status): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteOrganic([
        'type' => WasteType::Organic,
        'status' => $status,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldNotReceive('save');

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->schedule('pickup-id', [
        'pickup_date' => '2026-08-01',
    ]))->toThrow(DomainException::class, 'Only pending pickups can be scheduled.');
})->with([
    'scheduled' => WasteStatus::Scheduled,
    'completed' => WasteStatus::Completed,
    'canceled' => WasteStatus::Canceled,
]);

it('completes a scheduled pickup and creates its payment', function (
    string $modelClass,
    WasteType $type,
    string $amount,
): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $householdId = '507f1f77bcf86cd799439011';
    $now = Carbon::parse('2026-07-23 10:15:00');
    Carbon::setTestNow($now);

    /** @var Waste $waste */
    $waste = new $modelClass([
        'household_id' => $householdId,
        'type' => $type,
        'status' => WasteStatus::Scheduled,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(
            fn (Waste $candidate): bool => $candidate === $waste
                && $candidate->status === WasteStatus::Completed
        ))
        ->andReturn($waste);
    $paymentRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $payload) use ($householdId, $amount, $now): bool {
            return $payload['household_id'] instanceof ObjectId
                && (string) $payload['household_id'] === $householdId
                && $payload['amount'] === $amount
                && $payload['payment_date'] instanceof Carbon
                && $payload['payment_date']->equalTo($now->copy()->addWeek())
                && $payload['status'] === PaymentStatus::Pending;
        }))
        ->andReturn(new Payment);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect($service->complete('pickup-id'))->toBe($waste)
        ->and($waste->status)->toBe(WasteStatus::Completed);
})->with([
    'organic' => [WasteOrganic::class, WasteType::Organic, '50000.00'],
    'plastic' => [WastePlastic::class, WasteType::Plastic, '50000.00'],
    'paper' => [WastePaper::class, WasteType::Paper, '50000.00'],
    'electronic' => [WasteElectronic::class, WasteType::Electronic, '100000.00'],
]);

it('rejects completing a pickup that is not scheduled', function (WasteStatus $status): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteOrganic([
        'type' => WasteType::Organic,
        'status' => $status,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldNotReceive('save');
    $paymentRepository->shouldNotReceive('create');

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->complete('pickup-id'))
        ->toThrow(DomainException::class, 'Only scheduled pickups can be completed.');
})->with([
    'pending' => WasteStatus::Pending,
    'completed' => WasteStatus::Completed,
    'canceled' => WasteStatus::Canceled,
]);

it('does not create a payment when saving a completed pickup fails', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteOrganic([
        'type' => WasteType::Organic,
        'status' => WasteStatus::Scheduled,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldReceive('save')
        ->once()
        ->with($waste)
        ->andThrow(new RuntimeException('Waste save failed.'));
    $paymentRepository->shouldNotReceive('create');

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->complete('pickup-id'))
        ->toThrow(RuntimeException::class, 'Waste save failed.')
        ->and($waste->status)->toBe(WasteStatus::Completed);
});

it('propagates a payment creation failure after saving a completed pickup', function (): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $householdId = '507f1f77bcf86cd799439011';
    $waste = new WasteOrganic([
        'household_id' => $householdId,
        'type' => WasteType::Organic,
        'status' => WasteStatus::Scheduled,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldReceive('save')
        ->once()
        ->with($waste)
        ->andReturn($waste);
    $paymentRepository->shouldReceive('create')
        ->once()
        ->andThrow(new RuntimeException('Payment creation failed.'));

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->complete('pickup-id'))
        ->toThrow(RuntimeException::class, 'Payment creation failed.')
        ->and($waste->status)->toBe(WasteStatus::Completed);
});

it('cancels a pending or scheduled pickup', function (WasteStatus $status): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteOrganic([
        'type' => WasteType::Organic,
        'status' => $status,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldReceive('save')
        ->once()
        ->with(Mockery::on(
            fn (Waste $candidate): bool => $candidate === $waste
                && $candidate->status === WasteStatus::Canceled
        ))
        ->andReturn($waste);

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect($service->cancel('pickup-id'))->toBe($waste)
        ->and($waste->status)->toBe(WasteStatus::Canceled);
})->with([
    'pending' => WasteStatus::Pending,
    'scheduled' => WasteStatus::Scheduled,
]);

it('rejects canceling a completed or canceled pickup', function (WasteStatus $status): void {
    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
    $waste = new WasteOrganic([
        'type' => WasteType::Organic,
        'status' => $status,
    ]);

    $wasteRepository->shouldReceive('findOrFail')
        ->once()
        ->with('pickup-id')
        ->andReturn($waste);
    $wasteRepository->shouldNotReceive('save');

    $service = new WasteService($wasteRepository, $paymentRepository);

    expect(fn () => $service->cancel('pickup-id'))
        ->toThrow(
            DomainException::class,
            'Only pending or scheduled pickups can be canceled.'
        );
})->with([
    'completed' => WasteStatus::Completed,
    'canceled' => WasteStatus::Canceled,
]);
