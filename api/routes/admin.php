<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BusinessBranchController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BusinessTaxesController;
use App\Http\Controllers\BusinessTaxPaymentsController;
use App\Http\Controllers\EmployeeRemunerationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;


// Protected user routes
// Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
    Route::post("/business", [BusinessController::class, "store"]);
    Route::patch("/update-business", [BusinessController::class, "update"]);
     // ============== cashflow analytics ===================
     Route::get("/branches/cashflow/analytics", [CashFlowController::class, "analytics"]);

    // ============ Branches =============
    Route::apiResource("branches", BusinessBranchController::class)->only(["index", "show", "store", "update", "destroy"]);
    // ============ Roles =============
    Route::apiResource("roles", RoleController::class);
     // ============== Worker managed by admin===================
    Route::apiResource("workers", WorkerController::class);
     // ============== suppliers ===================
     Route::apiResource("suppliers", SupplierController::class);
     // ============== customers ===================
     Route::apiResource("customers", CustomerController::class);
     // ============== attendances ===================
     Route::apiResource("attendances", AttendanceController::class);
     // ============== business tax settings ===================
     Route::apiResource("business-taxes", BusinessTaxesController::class);
     // ============== employee remuneration ===================
     Route::apiResource("employee-remunerations", EmployeeRemunerationController::class);
      // ============== employee remuneration ===================
     Route::apiResource("tax-payments", BusinessTaxPaymentsController::class);


});
