<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products/search', [PosController::class, 'searchProducts']);
    Route::get('/customers/search', [PosController::class, 'searchCustomers']);

    Route::post('/cart/validate', [PosController::class, 'validateCart']);
    Route::post('/checkout', [PosController::class, 'checkout']);

    Route::post('/cart/hold', [PosController::class, 'holdCart']);
    Route::get('/cart/held', [PosController::class, 'getHeldCarts']);
    Route::get('/cart/resume/{id}', [PosController::class, 'resumeCart']);
    Route::delete('/cart/held/{id}', [PosController::class, 'deleteHeldCart']);
});
