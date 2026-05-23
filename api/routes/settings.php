<?php

use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\CreditSettingController;
use App\Http\Controllers\Settings\CustomersSettingsController;
use App\Http\Controllers\Settings\DebitSettingController;
use App\Http\Controllers\Settings\PaymentStatusController;
use App\Http\Controllers\Settings\PromotionsSettingsController;
use App\Http\Controllers\Settings\ReportsSettingsController;
use App\Http\Controllers\Settings\SuppliersSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function(){
    // ========== get ===========
    Route::get("/attendance-settings", [AttendanceSettingsController::class, "index"]);
    Route::get("/customer-settings", [CustomersSettingsController::class, "index"]);
    Route::get("/payment-settings", [PaymentStatusController::class, "index"]);
    Route::get("/promotion-settings", [PromotionsSettingsController::class, "index"]);
    Route::get("/reports-settings", [ReportsSettingsController::class, "index"]);
    Route::get("/supplier-settings", [SuppliersSettingsController::class, "index"]);
    Route::get("/credit-settings", [CreditSettingController::class, "index"]);
    Route::get("/debit-settings", [DebitSettingController::class, "index"]);

    // ======= post ============
    Route::put("/attendance-settings/{attendanceSetting}", [AttendanceSettingsController::class, "update"]);
    Route::put("/customer-settings/{customersSetting}", [CustomersSettingsController::class, "update"]);
    Route::put("/payment-settings/{paymentStatus}", [PaymentStatusController::class, "update"]);
    Route::put("/promotion-settings/{promotionsSetting}", [PromotionsSettingsController::class, "update"]);
    Route::put("/reports-settings/{reportsSetting}", [ReportsSettingsController::class, "update"]);
    Route::put("/supplier-settings/{suppliersSetting}", [SuppliersSettingsController::class, "update"]);
    Route::put("/credit-settings", [CreditSettingController::class, "update"]);
    Route::put("/debit-settings", [DebitSettingController::class, "update"]);
});