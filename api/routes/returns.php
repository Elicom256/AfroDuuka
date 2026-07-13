<?php

use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SaleReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource("purchase-returns", PurchaseReturnController::class)->only(["index", "store", "show"]);
    Route::apiResource("sale-returns", SaleReturnController::class)->only(["index", "store", "show"]);
    // Route::get('/branch-purchase-returns', [PurchaseReturnController::class, 'index']);
    // Route::post('/branch-purchase-returns', [PurchaseReturnController::class, 'store']);
    // Route::get('/branch-purchase-returns/{purchase_return}', [PurchaseReturnController::class, 'show']);
});
