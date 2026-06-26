<?php

use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ReportExportController::class, 'index'])->name('report-exports.index');
    Route::post('/', [ReportExportController::class, 'store'])->name('report-exports.store');
    Route::get('{reportExport}', [ReportExportController::class, 'show'])->name('report-exports.show');
    Route::match(['put', 'patch'], '{reportExport}', [ReportExportController::class, 'update'])->name('report-exports.update');
    Route::delete('{reportExport}', [ReportExportController::class, 'destroy'])->name('report-exports.destroy');
});
