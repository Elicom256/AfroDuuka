<?php

use App\Http\Controllers\SaleOrderController;
use App\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->prefix("sales-orders")->group(function () {
    // Route::get("/", [SaleOrderController::class, "index"]);
    // Route::post("/", [SaleOrderController::class, "store"]);
    // Route::get("/{sale_order}", [SaleOrderController::class, "show"]);
    // Route::put("/{sale_order}", [SaleOrderController::class, "update"]);
    // Route::delete("/{sale_order}", [SaleOrderController::class, "destroy"]);
    Route::apiResource("sale-orders", SaleOrderController::class);
    Route::apiResource("purchase-orders", PurchaseOrderController::class);
});

// Route::middleware("auth:sanctum")->prefix("purchase-orders")->group(function () {
//     Route::get("/", [PurchaseOrderController::class, "index"]);
//     Route::post("/", [PurchaseOrderController::class, "store"]);
//     Route::get("/{purchase_order}", [PurchaseOrderController::class, "show"]);
//     Route::put("/{purchase_order}", [PurchaseOrderController::class, "update"]);
//     Route::delete("/{purchase_order}", [PurchaseOrderController::class, "destroy"]);
// });
