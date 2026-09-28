<?php

use App\Http\Controllers\ProcurementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/procurement', [ProcurementController::class, 'overview']);
    Route::get('/procurement/reorder-suggestions', [ProcurementController::class, 'reorderSuggestions']);

    Route::get('/purchase-orders', [ProcurementController::class, 'index']);
    Route::post('/purchase-orders', [ProcurementController::class, 'store']);
    Route::get('/purchase-orders/{purchase_order}', [ProcurementController::class, 'show']);
    Route::post('/purchase-orders/{purchase_order}/approve', [ProcurementController::class, 'approve']);
    Route::post('/purchase-orders/{purchase_order}/order', [ProcurementController::class, 'order']);
    Route::post('/purchase-orders/{purchase_order}/receive', [ProcurementController::class, 'receive']);
    Route::post('/purchase-orders/{purchase_order}/cancel', [ProcurementController::class, 'cancel']);
});
