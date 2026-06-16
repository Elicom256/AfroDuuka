<?php

use App\Http\Controllers\PaymentGatewayController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/', PaymentGatewayController::class)->parameters(['' => 'paymentGateway']);
});
