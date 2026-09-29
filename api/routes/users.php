<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/login', [UserController::class, 'login'])->middleware('throttle:auth');
Route::post('/signup', [UserController::class, 'signup'])->middleware('throttle:auth');
// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);    
    Route::put('/workers/{worker}', [UserController::class, 'update']);
    Route::get('/workers', [UserController::class, 'workers']);
    Route::get('/workers/{worker}', [UserController::class, 'worker']);
    Route::delete('/workers/{worker}', [UserController::class, 'destroy']);

    // ================== NOTIFICATIONS ======================
    Route::apiResource('notifications', NotificationController::class)->only([
        'index', 'show', 'destroy'
    ]);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll']);

    // ================== Todos =============================== ->only(["index", "store", "show", "update", "destroy"]);
        Route::apiResource("todos", TodoController::class);


});
