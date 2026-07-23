<?php

use App\Contract\Repositories\WasteRepositoryInterface;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Jobs\CancelUncollectedOrganicWaste;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-25 17:00:00', 'UTC'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('the daily job cancels only overdue scheduled organic pickups', function (): void {
    $household = testCreateHousehold();

    $overdueOrganic = testCreateWaste($household, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Scheduled,
        'pickup_date' => Carbon::parse('2026-07-23 15:00:00'),
    ]);
    $recentOrganic = testCreateWaste($household, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Scheduled,
        'pickup_date' => Carbon::parse('2026-07-24 00:00:00'),
    ]);
    $completedOrganic = testCreateWaste($household, [
        'type' => WasteType::Organic,
        'status' => WasteStatus::Completed,
        'pickup_date' => Carbon::parse('2026-07-23 15:00:00'),
    ]);
    $overduePlastic = testCreateWaste($household, [
        'type' => WasteType::Plastic,
        'status' => WasteStatus::Scheduled,
        'pickup_date' => Carbon::parse('2026-07-23 15:00:00'),
    ]);

    (new CancelUncollectedOrganicWaste)->handle(
        app(WasteRepositoryInterface::class)
    );

    expect($overdueOrganic->refresh()->status)->toBe(WasteStatus::Canceled)
        ->and($recentOrganic->refresh()->status)->toBe(WasteStatus::Scheduled)
        ->and($completedOrganic->refresh()->status)->toBe(WasteStatus::Completed)
        ->and($overduePlastic->refresh()->status)->toBe(WasteStatus::Scheduled);
});
