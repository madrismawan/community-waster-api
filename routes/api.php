<?php

use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PickupController;
use App\Http\Controllers\Api\ReportController;
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

Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::put('{id}/confirm', [PaymentController::class, 'confirm']);
});

Route::prefix('reports')->group(function () {
    Route::get('waste-summary', [ReportController::class, 'wasteSummary']);
    Route::get('payment-summary', [ReportController::class, 'paymentSummary']);
    Route::get('households/{id}/history', [ReportController::class, 'householdHistory']);
});
