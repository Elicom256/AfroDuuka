<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/login', [UserController::class, 'login']);
Route::post('/signup', [UserController::class, 'signup']);
// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);    
    Route::put('/workers/{worker}', [UserController::class, 'update']);
    Route::get('/workers', [UserController::class, 'workers']);
    Route::get('/workers/{worker}', [UserController::class, 'worker']);
    Route::delete('/workers/{worker}', [UserController::class, 'destroy']);


});
