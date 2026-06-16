<?php

use App\Http\Controllers\CurrencyRateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/', CurrencyRateController::class)->parameters(['' => 'currencyRate']);
});
