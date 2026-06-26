<?php

use App\Http\Controllers\PrinterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [PrinterController::class, 'index'])->name('printers.index');
    Route::post('/', [PrinterController::class, 'store'])->name('printers.store');
    Route::get('{printer}', [PrinterController::class, 'show'])->name('printers.show');
    Route::match(['put', 'patch'], '{printer}', [PrinterController::class, 'update'])->name('printers.update');
    Route::delete('{printer}', [PrinterController::class, 'destroy'])->name('printers.destroy');
});
