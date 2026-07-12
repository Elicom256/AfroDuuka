<?php

use App\Http\Controllers\BusinessBranchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // ========== Specific routes BEFORE wildcard ==========
    Route::apiResource('categories', ProductCategoryController::class);
    Route::get("/analytics", [ProductController::class, "inventoryAnalytics"]);
    Route::get("/expiring", [ProductController::class, "expiringAnalytics"]);
    Route::get("/restocking", [ProductController::class, "restocking"]);
    
    //  ============ others ============
    Route::get("/dynamics", [BusinessBranchController::class, "salesAndPurchases"]);

    // ======== CRUD (wildcard routes last) ============
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
     // ========== Metrics after wildcard (needs {product}) ==========
    Route::get("/{product}/metrics", [ProductController::class, "productMetrics"]);
    Route::get('/{product}', [ProductController::class, 'show']);
    Route::match(['put', 'patch'], '/{product}', [ProductController::class, 'update']);
    Route::delete('/{product}', [ProductController::class, 'destroy']);

   
});
