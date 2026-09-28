<?php

use App\Http\Controllers\ProductLossController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProductLossController::class, 'index']);
    Route::post('/', [ProductLossController::class, 'store']);
    Route::get('/summary', [ProductLossController::class, 'summary']);
    Route::get('/{productLoss}', [ProductLossController::class, 'show']);
});
