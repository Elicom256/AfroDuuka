<?php

use App\Http\Controllers\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [TaxRateController::class, 'index']);
    Route::post('/', [TaxRateController::class, 'store']);
    Route::get('/{taxRate}', [TaxRateController::class, 'show']);
    Route::match(['put', 'patch'], '/{taxRate}', [TaxRateController::class, 'update']);
    Route::delete('/{taxRate}', [TaxRateController::class, 'destroy']);
});