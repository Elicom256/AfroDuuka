<?php

use App\Http\Controllers\CouponController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/", [CouponController::class, "index"]);
    Route::post("/", [CouponController::class, "store"]);
    Route::get("/{coupon}", [CouponController::class, "show"]);
    Route::put("/{coupon}", [CouponController::class, "update"]);
    Route::delete("/{coupon}", [CouponController::class, "destroy"]);
});
