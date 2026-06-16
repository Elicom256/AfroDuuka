<?php

use App\Http\Controllers\PrinterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/', PrinterController::class)->parameters(['' => 'printer']);
});
