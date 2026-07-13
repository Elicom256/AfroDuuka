<?php

use App\Http\Controllers\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/branch-purchase-returns', [PurchaseReturnController::class, 'index']);
    Route::post('/branch-purchase-returns', [PurchaseReturnController::class, 'store']);
    Route::get('/branch-purchase-returns/{purchase_return}', [PurchaseReturnController::class, 'show']);
});
