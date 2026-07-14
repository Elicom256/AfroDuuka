<?php

/*-----------------------------------------------------------------------------------
 * Route: price-history.php
 * -------------------------------
 * Price history endpoints.
 * Specific action routes (analytics) before parameterised ones.
 *---------------------------------------------------------------------------------*/

use App\Http\Controllers\PriceHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Analytics (before wildcard routes)
    Route::get('/analytics', [PriceHistoryController::class, 'analytics']);

    // List latest changes (filterable)
    Route::get('/', [PriceHistoryController::class, 'index']);
});
