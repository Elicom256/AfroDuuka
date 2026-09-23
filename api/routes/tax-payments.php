<?php

use App\Http\Controllers\TaxPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/analytics', [TaxPaymentController::class, 'analytics']);
    Route::get('/', [TaxPaymentController::class, 'index']);
    Route::post('/', [TaxPaymentController::class, 'store']);
    Route::get('/{taxPayment}', [TaxPaymentController::class, 'show']);
    Route::match(['put', 'patch'], '/{taxPayment}', [TaxPaymentController::class, 'update']);
    Route::delete('/{taxPayment}', [TaxPaymentController::class, 'destroy']);
});