<?php

use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/', ReportExportController::class)->parameters(['' => 'reportExport']);
});
