<?php

use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\CreditSettingController;
use App\Http\Controllers\Settings\CustomersSettingsController;
use App\Http\Controllers\Settings\DebitSettingController;
use App\Http\Controllers\Settings\PaymentMethodController;
use App\Http\Controllers\Settings\PromotionsSettingsController;
use App\Http\Controllers\Settings\ReportsSettingsController;
use App\Http\Controllers\Settings\SuppliersSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function(){
    Route::apiResource("attendance-settings", AttendanceSettingsController::class)->only(["index", "update", "show"]);
    Route::apiResource("customers-settings", CustomersSettingsController::class)->only(["index", "update", "show"]);
    Route::apiResource("payment-methods", PaymentMethodController::class)->only(["index", "update", "show"]);
    Route::apiResource("promotions-settings", PromotionsSettingsController::class)->only(["index", "update", "show"]);
    Route::apiResource("reports-settings", ReportsSettingsController::class)->only(["index", "update", "show"]);
    Route::apiResource("suppliers-settings", SuppliersSettingsController::class)->only(["index", "update", "show"]);
    Route::apiResource("credit-settings", CreditSettingController::class)->only(["index", "update", "show"]);
    Route::apiResource("debit-settings", DebitSettingController::class)->only(["index", "update", "show"]);
});
