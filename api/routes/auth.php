<?php

use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

// Public auth routes
// Route::post('/login', [PurchaseController::class, 'login']);
// Route::get('/', [PurchaseController::class, 'index']);
// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [PurchaseController::class, 'me']);
    Route::post('/logout', [PurchaseController::class, 'logout']);
    
    Route::post('/', [PurchaseController::class, 'store']);
    Route::get('/{user}', [PurchaseController::class, 'show']);
    Route::put('/{user}', [PurchaseController::class, 'update']);
    Route::delete('/{user}', [PurchaseController::class, 'destroy']);
});
