<?php

use App\Http\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PlanController::class, 'index'])->withoutMiddleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/', [PlanController::class, 'store']);
    Route::get('{plan}', [PlanController::class, 'show']);
    Route::match(['put', 'patch'], '{plan}', [PlanController::class, 'update']);
    Route::delete('{plan}', [PlanController::class, 'destroy']);
});
