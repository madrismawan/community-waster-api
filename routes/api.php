<?php

use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\PickupController;
use Illuminate\Support\Facades\Route;

Route::prefix('households')->group(function () {
    Route::get('/', [HouseholdController::class, 'index']);
    Route::post('/', [HouseholdController::class, 'store']);
    Route::get('{id}', [HouseholdController::class, 'show']);
    Route::put('{id}', [HouseholdController::class, 'update']);
    Route::delete('{id}', [HouseholdController::class, 'destroy']);
});

Route::prefix('pickups')->group(function () {
    Route::get('/', [PickupController::class, 'index']);
    Route::post('/', [PickupController::class, 'store']);
    Route::put('{id}/schedule', [PickupController::class, 'schedule']);
    Route::put('{id}/complete', [PickupController::class, 'complete']);
    Route::put('{id}/cancel', [PickupController::class, 'cancel']);
});