<?php

use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::post("/chat", [AiController::class, "chat"]);
    Route::get("/tools", [AiController::class, "tools"]);
});
