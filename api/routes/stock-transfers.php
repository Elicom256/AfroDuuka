<?php

use App\Http\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/{stockTransfer}/dispatch', [StockTransferController::class, 'dispatch']);
    Route::post('/{stockTransfer}/receive', [StockTransferController::class, 'receive']);
    Route::post('/{stockTransfer}/cancel', [StockTransferController::class, 'cancel']);
    Route::apiResource('/', StockTransferController::class)->parameters(['' => 'stockTransfer']);
});
