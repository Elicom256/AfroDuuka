<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products/search', [PosController::class, 'searchProducts']);
    Route::get('/customers/search', [PosController::class, 'searchCustomers']);

    Route::post('/cart/validate', [PosController::class, 'validateCart']);
    Route::post('/checkout', [PosController::class, 'checkout']);

    Route::post('/sales/hold', [PosController::class, 'holdSale']);
    Route::get('/sales/held', [PosController::class, 'getHeldSales']);
    Route::get('/sales/held/{id}', [PosController::class, 'resumeHeldSale']);
    Route::delete('/sales/held/{id}', [PosController::class, 'deleteHeldSale']);
});