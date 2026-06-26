<?php

use App\Http\Controllers\TaxInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/{taxInvoice}/submit-to-ura', [TaxInvoiceController::class, 'submitToUra']);
    Route::get('/', [TaxInvoiceController::class, 'index'])->name('tax-invoices.index');
    Route::post('/', [TaxInvoiceController::class, 'store'])->name('tax-invoices.store');
    Route::get('{taxInvoice}', [TaxInvoiceController::class, 'show'])->name('tax-invoices.show');
    Route::match(['put', 'patch'], '{taxInvoice}', [TaxInvoiceController::class, 'update'])->name('tax-invoices.update');
    Route::delete('{taxInvoice}', [TaxInvoiceController::class, 'destroy'])->name('tax-invoices.destroy');
});
