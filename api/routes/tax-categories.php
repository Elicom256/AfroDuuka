<?php

use App\Http\Controllers\TaxCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [TaxCategoryController::class, 'index']);
    Route::post('/', [TaxCategoryController::class, 'store']);
    Route::get('/{taxCategory}', [TaxCategoryController::class, 'show']);
    Route::match(['put', 'patch'], '/{taxCategory}', [TaxCategoryController::class, 'update']);
    Route::delete('/{taxCategory}', [TaxCategoryController::class, 'destroy']);
});