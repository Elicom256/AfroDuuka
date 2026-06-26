<?php

use App\Http\Controllers\CurrencyRateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [CurrencyRateController::class, 'index'])->name('currency-rates.index');
    Route::post('/', [CurrencyRateController::class, 'store'])->name('currency-rates.store');
    Route::get('{currencyRate}', [CurrencyRateController::class, 'show'])->name('currency-rates.show');
    Route::match(['put', 'patch'], '{currencyRate}', [CurrencyRateController::class, 'update'])->name('currency-rates.update');
    Route::delete('{currencyRate}', [CurrencyRateController::class, 'destroy'])->name('currency-rates.destroy');
});
