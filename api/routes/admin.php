<?php

use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\BusinessController;
use Illuminate\Support\Facades\Route;


// Protected user routes
// Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
    Route::post("/business", [BusinessController::class, "store"]);
    Route::patch("/update-business", [BusinessController::class, "store"]);
});
