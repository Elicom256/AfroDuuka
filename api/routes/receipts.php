<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ReceiptController::class, 'index']);
    Route::get('/{receipt}', [ReceiptController::class, 'show']);
    Route::get('/{receipt}/pdf', [ReceiptController::class, 'pdf']);
});
