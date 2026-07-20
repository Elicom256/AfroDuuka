<?php

use App\Http\Controllers\PromotionController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/", [PromotionController::class, "index"]);
    Route::post("/", [PromotionController::class, "store"]);
    Route::get("/{promotion}", [PromotionController::class, "show"]);
    Route::put("/{promotion}", [PromotionController::class, "update"]);
    Route::delete("/{promotion}", [PromotionController::class, "destroy"]);
});
