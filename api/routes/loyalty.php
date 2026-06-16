<?php

use App\Http\Controllers\LoyaltyCardController;
use App\Http\Controllers\LoyaltyProgramController;
use App\Http\Controllers\LoyaltyRewardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Loyalty programs
    Route::apiResource('programs', LoyaltyProgramController::class);

    // Loyalty cards with point operations
    Route::post('/cards/{loyaltyCard}/earn', [LoyaltyCardController::class, 'earnPoints']);
    Route::post('/cards/{loyaltyCard}/burn', [LoyaltyCardController::class, 'burnPoints']);
    Route::post('/cards/{loyaltyCard}/adjust', [LoyaltyCardController::class, 'adjustPoints']);
    Route::apiResource('cards', LoyaltyCardController::class);

    // Loyalty rewards
    Route::apiResource('rewards', LoyaltyRewardController::class);
});
