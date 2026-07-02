<?php

use App\Http\Controllers\PricingController;
use Illuminate\Support\Facades\Route;

Route::get("/", [PricingController::class, 'index'])->withoutMiddleware("auth:sanctum");

Route::middleware("auth:sanctum")->group(function () {
    Route::apiResource("pricings", PricingController::class)->except(["index"]);
});
