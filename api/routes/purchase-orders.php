<?php

use App\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index']);
    Route::post('/', [PurchaseOrderController::class, 'store']);
    Route::get('/{purchase_order}', [PurchaseOrderController::class, 'show']);
    Route::put('/{purchase_order}', [PurchaseOrderController::class, 'update']);
    Route::delete('/{purchase_order}', [PurchaseOrderController::class, 'destroy']);
});
