<?php

use App\Http\Controllers\ReorderRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/', ReorderRuleController::class)->parameters(['' => 'reorderRule']);
});
