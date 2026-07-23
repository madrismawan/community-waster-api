<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Factories\WasteFactory;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/** @return array<string, string> */
function testAuthHeaders(): array
{
    $user = User::query()->create([
        'name' => 'Test User',
        'email' => Str::uuid().'@example.test',
        'password' => 'password',
    ]);

    $token = Auth::guard('api')->tokenById($user->getAuthIdentifier());
    Auth::forgetGuards();

    return [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
    ];
}

/** @param array<string, mixed> $overrides */
function testCreateHousehold(array $overrides = []): Household
{
    return Household::query()->create([
        'owner_name' => 'Test Household',
        'address' => 'Test Address',
        'block' => 'A',
        'no' => '01',
        ...$overrides,
    ]);
}

/** @param array<string, mixed> $overrides */
function testCreateWaste(Household $household, array $overrides = []): Waste
{
    $attributes = [
        'household_id' => new ObjectId((string) $household->getKey()),
        'type' => WasteType::Organic,
        'pickup_date' => null,
        'status' => WasteStatus::Pending,
        ...$overrides,
    ];

    $waste = app(WasteFactory::class)->make($attributes);
    $waste->save();

    return $waste;
}

/** @param array<string, mixed> $overrides */
function testCreatePayment(Household $household, array $overrides = []): Payment
{
    return Payment::query()->create([
        'household_id' => new ObjectId((string) $household->getKey()),
        'amount' => '50000.00',
        'payment_date' => now()->addWeek(),
        'status' => PaymentStatus::Pending,
        ...$overrides,
    ]);
}
