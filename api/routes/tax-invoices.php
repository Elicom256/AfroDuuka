<?php

use App\Http\Controllers\TaxInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/{taxInvoice}/submit-to-ura', [TaxInvoiceController::class, 'submitToUra']);
    Route::apiResource('/', TaxInvoiceController::class)->parameters(['' => 'taxInvoice']);
});
