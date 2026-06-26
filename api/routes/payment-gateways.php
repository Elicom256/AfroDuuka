<?php

use App\Http\Controllers\PaymentGatewayController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [PaymentGatewayController::class, 'index'])->name('payment-gateways.index');
    Route::post('/', [PaymentGatewayController::class, 'store'])->name('payment-gateways.store');
    Route::get('{paymentGateway}', [PaymentGatewayController::class, 'show'])->name('payment-gateways.show');
    Route::match(['put', 'patch'], '{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('payment-gateways.update');
    Route::delete('{paymentGateway}', [PaymentGatewayController::class, 'destroy'])->name('payment-gateways.destroy');
});
