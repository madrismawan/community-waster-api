<?php

use App\Contract\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;
use App\Services\HouseholdService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

afterEach(function (): void {
    Mockery::close();
});

it('paginates households with default pagination values', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $paginator = Mockery::mock(LengthAwarePaginator::class);

    $repository->shouldReceive('paginateFiltered')
        ->once()
        ->with(15, [], 1)
        ->andReturn($paginator);

    $service = new HouseholdService($repository);

    expect($service->paginate())->toBe($paginator);
});

it('paginates households with requested filters and integer pagination values', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $paginator = Mockery::mock(LengthAwarePaginator::class);
    $request = [
        'search' => 'Made',
        'block' => 'A',
        'per_page' => '25',
        'page' => '3',
    ];

    $repository->shouldReceive('paginateFiltered')
        ->once()
        ->with(25, $request, 3)
        ->andReturn($paginator);

    $service = new HouseholdService($repository);

    expect($service->paginate($request))->toBe($paginator);
});

it('finds a household by id', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $household = new Household;

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('household-id')
        ->andReturn($household);

    $service = new HouseholdService($repository);

    expect($service->find('household-id'))->toBe($household);
});

it('propagates a not found exception when finding a household', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $exception = (new ModelNotFoundException)->setModel(Household::class, ['missing-id']);

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('missing-id')
        ->andThrow($exception);

    $service = new HouseholdService($repository);

    expect(fn () => $service->find('missing-id'))
        ->toThrow(ModelNotFoundException::class);
});

it('creates a household using only fillable attributes', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $household = new Household;
    $request = [
        'owner_name' => 'Made',
        'address' => 'Jalan Melati',
        'block' => 'A',
        'no' => '12',
        'ignored' => 'must not be persisted',
    ];
    $payload = [
        'owner_name' => 'Made',
        'address' => 'Jalan Melati',
        'block' => 'A',
        'no' => '12',
    ];

    $repository->shouldReceive('create')
        ->once()
        ->with($payload)
        ->andReturn($household);

    $service = new HouseholdService($repository);

    expect($service->create($request))->toBe($household);
});

it('updates a household using only fillable attributes', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $household = new Household;
    $updatedHousehold = new Household;
    $request = [
        'owner_name' => 'Komang',
        'ignored' => 'must not be persisted',
    ];

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('household-id')
        ->andReturn($household);
    $repository->shouldReceive('update')
        ->once()
        ->with($household, ['owner_name' => 'Komang'])
        ->andReturn($updatedHousehold);

    $service = new HouseholdService($repository);

    expect($service->update('household-id', $request))->toBe($updatedHousehold);
});

it('does not update when the household cannot be found', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $exception = (new ModelNotFoundException)->setModel(Household::class, ['missing-id']);

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('missing-id')
        ->andThrow($exception);
    $repository->shouldNotReceive('update');

    $service = new HouseholdService($repository);

    expect(fn () => $service->update('missing-id', ['owner_name' => 'Komang']))
        ->toThrow(ModelNotFoundException::class);
});

it('deletes an existing household', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $household = new Household;

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('household-id')
        ->andReturn($household);
    $repository->shouldReceive('delete')
        ->once()
        ->with($household);

    $service = new HouseholdService($repository);

    $service->delete('household-id');
});

it('does not delete when the household cannot be found', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $exception = (new ModelNotFoundException)->setModel(Household::class, ['missing-id']);

    $repository->shouldReceive('findOrFail')
        ->once()
        ->with('missing-id')
        ->andThrow($exception);
    $repository->shouldNotReceive('delete');

    $service = new HouseholdService($repository);

    expect(fn () => $service->delete('missing-id'))
        ->toThrow(ModelNotFoundException::class);
});

it('restores a soft deleted household', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $household = new Household;
    $restoredHousehold = new Household;

    $repository->shouldReceive('findTrashedOrFail')
        ->once()
        ->with('household-id')
        ->andReturn($household);
    $repository->shouldReceive('restore')
        ->once()
        ->with($household)
        ->andReturn($restoredHousehold);

    $service = new HouseholdService($repository);

    expect($service->restore('household-id'))->toBe($restoredHousehold);
});

it('does not restore when a soft deleted household cannot be found', function (): void {
    $repository = Mockery::mock(HouseholdRepositoryInterface::class);
    $exception = (new ModelNotFoundException)->setModel(Household::class, ['missing-id']);

    $repository->shouldReceive('findTrashedOrFail')
        ->once()
        ->with('missing-id')
        ->andThrow($exception);
    $repository->shouldNotReceive('restore');

    $service = new HouseholdService($repository);

    expect(fn () => $service->restore('missing-id'))
        ->toThrow(ModelNotFoundException::class);
});
