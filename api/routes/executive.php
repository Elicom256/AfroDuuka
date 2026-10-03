<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BusinessBranchController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeRemunerationController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;


// Protected user routes
// Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
Route::middleware('auth:sanctum')->group(function () {
    Route::get("/business-categories", [BusinessCategoryController::class, "index"]);
    Route::get("/business", [BusinessController::class, "show"]);
    Route::post("/business", [BusinessController::class, "store"]);
    Route::patch("/business", [BusinessController::class, "update"]);
     // ============== cashflow analytics ===================
     Route::get("/branches/cashflow/analytics", [CashFlowController::class, "analytics"]);

    // ============ Branches =============
    // Registered before the resource so the literal segment wins over {branch}.
    Route::get("/branches/dynamics", [BusinessBranchController::class, "salesAndPurchases"]);
    Route::apiResource("branches", BusinessBranchController::class)->only(["index", "show", "store", "update", "destroy"]);
    // ============ Roles =============
    Route::apiResource("roles", RoleController::class);
     // ============== Worker managed by executive===================
    Route::apiResource("workers", WorkerController::class);
     // ============== suppliers ===================
     Route::apiResource("suppliers", SupplierController::class);
     Route::post("suppliers/{supplier}/attachments", [AttachmentController::class, "storeSupplier"]);
     Route::get("suppliers/{supplier}/attachments", [AttachmentController::class, "indexSupplier"]);
     Route::delete("suppliers/{supplier}/attachments/{attachment}", [AttachmentController::class, "destroySupplier"]);
     // ============== customers ===================
     Route::apiResource("customers", CustomerController::class);
     Route::post("customers/{customer}/attachments", [AttachmentController::class, "storeCustomer"]);
     Route::get("customers/{customer}/attachments", [AttachmentController::class, "indexCustomer"]);
     Route::delete("customers/{customer}/attachments/{attachment}", [AttachmentController::class, "destroyCustomer"]);
     // ============== attendances ===================
     Route::apiResource("attendances", AttendanceController::class);
      // ============== employee remuneration ===================
     Route::apiResource("employee-remuneration", EmployeeRemunerationController::class);
      // ============== employee salary ===================
     Route::apiResource("employee-salary", EmployeeSalaryController::class);
     // ============== activity logs ===================
     Route::get("activity-logs/categories", [ActivityLogController::class, "categories"]);
     Route::apiResource("activity-logs", ActivityLogController::class)->only(["index", "show", "destroy"]);

});
