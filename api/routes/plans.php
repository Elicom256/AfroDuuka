<?php

use App\Http\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PlanController::class, 'index'])->withoutMiddleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('plans', PlanController::class);
});
