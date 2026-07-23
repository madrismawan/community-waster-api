<?php

use App\Contract\Repositories\ReportRepositoryInterface;
use App\Models\Household;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

afterEach(function (): void {
    Mockery::close();
});

it('returns the waste summary from the repository', function (): void {
    $repository = Mockery::mock(ReportRepositoryInterface::class);
    $summary = [
        [
            'type' => 'organic',
            'status' => 'completed',
            'total_pickups' => 3,
        ],
    ];

    $repository->shouldReceive('wasteSummary')
        ->once()
        ->andReturn($summary);

    $service = new ReportService($repository);

    expect($service->wasteSummary())->toBe($summary);
});

it('returns the payment summary from the repository', function (): void {
    $repository = Mockery::mock(ReportRepositoryInterface::class);
    $summary = [
        'payments_by_status' => [
            [
                'status' => 'paid',
                'total_payments' => 2,
                'total_amount' => '100000.00',
            ],
        ],
        'total_revenue' => '100000.00',
    ];

    $repository->shouldReceive('paymentSummary')
        ->once()
        ->andReturn($summary);

    $service = new ReportService($repository);

    expect($service->paymentSummary())->toBe($summary);
});

it('returns household pickup and payment history from the repository', function (): void {
    $repository = Mockery::mock(ReportRepositoryInterface::class);
    $history = [
        'id' => '507f1f77bcf86cd799439011',
        'owner_name' => 'Made',
        'pickups' => [],
        'payments' => [],
    ];

    $repository->shouldReceive('householdHistory')
        ->once()
        ->with('507f1f77bcf86cd799439011')
        ->andReturn($history);

    $service = new ReportService($repository);

    expect($service->householdHistory('507f1f77bcf86cd799439011'))->toBe($history);
});

it('throws a model not found exception when household history does not exist', function (): void {
    $repository = Mockery::mock(ReportRepositoryInterface::class);
    $householdId = '507f1f77bcf86cd799439011';

    $repository->shouldReceive('householdHistory')
        ->once()
        ->with($householdId)
        ->andReturnNull();

    $service = new ReportService($repository);

    try {
        $service->householdHistory($householdId);
    } catch (ModelNotFoundException $exception) {
        expect($exception->getModel())->toBe(Household::class)
            ->and($exception->getIds())->toBe([$householdId]);

        return;
    }

    test()->fail('A ModelNotFoundException was not thrown.');
});

it('propagates repository errors from report methods', function (string $method): void {
    $repository = Mockery::mock(ReportRepositoryInterface::class);

    $repository->shouldReceive($method)
        ->once()
        ->andThrow(new RuntimeException('Aggregation failed.'));

    $service = new ReportService($repository);

    expect(fn () => $service->{$method}())
        ->toThrow(RuntimeException::class, 'Aggregation failed.');
})->with([
    'waste summary' => 'wasteSummary',
    'payment summary' => 'paymentSummary',
]);
