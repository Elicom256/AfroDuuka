<?php

use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\CustomersSettingsController;
use App\Http\Controllers\Settings\PaymentStatusController;
use App\Http\Controllers\Settings\PromotionsSettingsController;
use App\Http\Controllers\Settings\ReportsSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function(){
    // ========== get ===========
    Route::get("/attendance-settings", [AttendanceSettingsController::class, "index"]);
    Route::get("/customer-settings", [CustomersSettingsController::class, "index"]);
    Route::get("/payment-settings", [PaymentStatusController::class, "index"]);
    Route::get("/promotion-settings", [PromotionsSettingsController::class, "index"]);
    Route::get("/reports-settings", [ReportsSettingsController::class, "index"]);

    // ======= post ============
    Route::post("/attendance-settings", [AttendanceSettingsController::class, "store"]);
    Route::post("/customer-settings", [CustomersSettingsController::class, "store"]);
    Route::post("/payment-settings", [PaymentStatusController::class, "store"]);
    Route::post("/promotion-settings", [PromotionsSettingsController::class, "store"]);
    Route::post("/reports-settings", [ReportsSettingsController::class, "store"]);
});