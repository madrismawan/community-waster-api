<?php

use App\Http\Controllers\Api\HouseholdController;
use Illuminate\Support\Facades\Route;

Route::get('households', [HouseholdController::class, 'index']);
Route::post('households', [HouseholdController::class, 'store']);
Route::get('households/{household}', [HouseholdController::class, 'show']);
Route::patch('households/{household}', [HouseholdController::class, 'update']);
Route::delete('households/{household}', [HouseholdController::class, 'destroy']);
