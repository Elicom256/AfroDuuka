<?php

use App\Http\Controllers\BusinessBranchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // ======== CRUD ============
    Route::apiResource('categories', ProductCategoryController::class);
    Route::get("/products/analytics", [ProductController::class, "inventoryAnalytics"]);
    Route::get("/products/expiring", [ProductController::class, "expiringAnalytics"]);
    Route::get("/products/restocking", [ProductController::class, "restocking"]);
    Route::get("/products/{product}/metrics", [ProductController::class, "productMetrics"]);
    Route::apiResource('products', ProductController::class)->only([
        "index", "show", "store", "update", "destroy"
    ]);

    //  ============ others ============
    Route::get("/products/dynamics", [BusinessBranchController::class, "salesAndPurchases"]);
});
