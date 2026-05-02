<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Public auth routes
// Route::post('/login', [ProductController::class, 'login']);
// Route::get('/', [ProductController::class, 'index']);
// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [ProductController::class, 'me']);
    Route::post('/logout', [ProductController::class, 'logout']);
    
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{user}', [ProductController::class, 'show']);
    Route::put('/{user}', [ProductController::class, 'update']);
    Route::delete('/{user}', [ProductController::class, 'destroy']);
});
