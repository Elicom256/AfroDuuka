<?php

use App\Http\Controllers\SaleReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/branch-sale-returns', [SaleReturnController::class, 'index']);
    Route::post('/branch-sale-returns', [SaleReturnController::class, 'store']);
    Route::get('/branch-sale-returns/{sale_return}', [SaleReturnController::class, 'show']);
});
