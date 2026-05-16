<?php

use App\Http\Controllers\BusinessBranchProductController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('business-products', ProductController::class);
    Route::apiResource('business-branch-products', BusinessBranchProductController::class);
});

// Protected user routes
// Route::middleware('auth:sanctum')->group(function () {
//     // product categories sold by the business
//     Route::post('/', [CategoryController::class, 'store']);
//     Route::get('/', [CategoryController::class, 'show']);
//     Route::put('/{user}', [CategoryController::class, 'update']);
//     Route::delete('/{user}', [CategoryController::class, 'destroy']);

//     // business products
//     Route::post('/', [ProductController::class, 'store']);
//     Route::get('/{user}', [ProductController::class, 'show']);
//     Route::put('/{user}', [ProductController::class, 'update']);
//     Route::delete('/{user}', [ProductController::class, 'destroy']);
// });
