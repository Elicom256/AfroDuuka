<?php

use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/login', [SaleController::class, 'login']);
// Route::get('/', [SaleController::class, 'index']);
// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [SaleController::class, 'me']);
    Route::post('/logout', [SaleController::class, 'logout']);
    
    Route::post('/add-worker', [SaleController::class, 'store']);
    Route::get('/{user}', [SaleController::class, 'show']);
    Route::put('/{user}', [SaleController::class, 'update']);
    Route::delete('/{user}', [SaleController::class, 'destroy']);
});
