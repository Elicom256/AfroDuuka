<?php

use App\Http\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/{stockTransfer}/dispatch', [StockTransferController::class, 'dispatch']);
    Route::post('/{stockTransfer}/receive', [StockTransferController::class, 'receive']);
    Route::post('/{stockTransfer}/cancel', [StockTransferController::class, 'cancel']);
    Route::get('/', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::post('/', [StockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::get('{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
    Route::match(['put', 'patch'], '{stockTransfer}', [StockTransferController::class, 'update'])->name('stock-transfers.update');
    Route::delete('{stockTransfer}', [StockTransferController::class, 'destroy'])->name('stock-transfers.destroy');
});
