<?php

use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


// Protected user routes
// Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
    Route::post("/business", [BusinessController::class, "store"]);
    Route::patch("/update-business", [BusinessController::class, "store"]);

    // ============ Roles =============
    Route::get("/roles", [RoleController::class, "index"]);
    Route::post("/roles", [RoleController::class, "store"]);
    Route::put("/roles/{role}", [RoleController::class, "update"]);
    Route::get("/roles/{role}", [RoleController::class, "show"]);
    Route::delete("/roles/{role}", [RoleController::class, "delete"]);

     // ============== Worker managed by admin===================
    Route::get('/workers', [UserController::class, 'index']);
    Route::post('/workers', [UserController::class, 'store']);
    Route::get('/workers/{user}', [UserController::class, 'show']);
    Route::delete('/workers/{user}', [UserController::class, 'destroy']);
    Route::patch('/workers/{user}', [UserController::class, 'update']); 

     // ============== suppliers ===================
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{user}', [SupplierController::class, 'show']);
    Route::delete('/suppliers/{user}', [SupplierController::class, 'destroy']);
    Route::patch('/suppliers/{user}', [SupplierController::class, 'update']); 
});
