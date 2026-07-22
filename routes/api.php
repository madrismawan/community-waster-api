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