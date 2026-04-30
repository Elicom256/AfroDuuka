<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/login', [UserController::class, 'login']);

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    // Get logged in user - Requirement from todaysWork.md
    Route::get('/me', [UserController::class, 'me']);
    
    // Logout
    Route::post('/logout', [UserController::class, 'logout']);
    
    // RESTful resource routes for user management
    Route::get('/', [UserController::class, 'index']);
    
    // POST /api/users - Create new user
    Route::post('/', [UserController::class, 'store']);
    
    // GET /api/users/{user} - Show specific user
    Route::get('/{user}', [UserController::class, 'show']);
    
    // PUT /api/users/{user} - Update user
    Route::put('/{user}', [UserController::class, 'update']);
    
    // DELETE /api/users/{user} - Delete user
    Route::delete('/{user}', [UserController::class, 'destroy']);
});
