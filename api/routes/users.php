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
    // User directory and worker management are executive-level: the index exposes
    // every user's role, and a worker update is how a role gets reassigned.
    Route::middleware('role')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/workers', [UserController::class, 'store']);
        Route::put('/workers/{user}', [UserController::class, 'update']);
        Route::delete('/workers/{user}', [UserController::class, 'destroy']);
    });

    Route::get('/me', [UserController::class, 'me']);
    Route::patch('/update', [UserController::class, 'updateProfile']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/workers', [UserController::class, 'workers']);
    Route::get('/workers/{user}', [UserController::class, 'worker']);

    // ================== NOTIFICATIONS ======================
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::apiResource('notifications', NotificationController::class)->only([
        'index', 'show', 'destroy',
    ]);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll']);

    // ================== Todos =============================== ->only(["index", "store", "show", "update", "destroy"]);
    Route::apiResource('todos', TodoController::class);

});
