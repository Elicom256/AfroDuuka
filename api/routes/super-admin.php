<?php

use App\Http\Controllers\SuperAdminBusinessController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/businesses", [SuperAdminBusinessController::class, "index"]);
    Route::get("/businesses/{business}", [SuperAdminBusinessController::class, "show"]);
    Route::patch("/businesses/{business}/status", [SuperAdminBusinessController::class, "updateStatus"]);
});
