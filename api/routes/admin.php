<?php

use App\Http\Controllers\BusinessBranchController;
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

    // ============ Branches =============
    Route::get("/branches", [BusinessBranchController::class, "index"]);
    Route::post("/branches", [BusinessBranchController::class, "store"]);
    Route::put("/branches/{branch}", [BusinessBranchController::class, "update"]);
    Route::get("/branches/{branch}", [BusinessBranchController::class, "show"]);
    Route::delete("/branches/{branch}", [BusinessBranchController::class, "delete"]);
    Route::get("/branches/branch/dynamics", [BusinessBranchController::class, "salesAndPurchases"]);

    // ============ Roles =============
    Route::get("/roles", [RoleController::class, "index"]);
    Route::post("/roles", [RoleController::class, "store"]);
    Route::put("/roles/{role}", [RoleController::class, "update"]);
    Route::get("/roles/{role}", [RoleController::class, "show"]);
    Route::delete("/roles/{role}", [RoleController::class, "delete"]);

     // ============== Worker managed by admin===================
    Route::get('/workers', [UserController::class, 'index']);
    Route::post('/workers', [UserController::class, 'store']);
    Route::get('/workers/{worker}', [UserController::class, 'show']);
    Route::delete('/workers/{worker}', [UserController::class, 'destroy']);
    Route::put('/workers/{worker}', [UserController::class, 'update']);

     // ============== suppliers ===================
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{user}', [SupplierController::class, 'show']);
    Route::delete('/suppliers/{user}', [SupplierController::class, 'destroy']);
    Route::patch('/suppliers/{user}', [SupplierController::class, 'update']); 
});
