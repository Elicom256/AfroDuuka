<?php

use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get("/", [QuotationController::class, "index"]);
    Route::post("/", [QuotationController::class, "store"]);

    // Explicit sub-routes before the {quotation} wildcard (mirror products.php ordering).
    Route::post("/{quotation}/send", [QuotationController::class, "send"]);
    Route::post("/{quotation}/accept", [QuotationController::class, "accept"]);
    Route::post("/{quotation}/cancel", [QuotationController::class, "cancel"]);
    Route::get("/{quotation}/pdf", [QuotationController::class, "pdf"]);

    Route::get("/{quotation}", [QuotationController::class, "show"]);
    Route::match(["put", "patch"], "/{quotation}", [QuotationController::class, "update"]);
    Route::delete("/{quotation}", [QuotationController::class, "destroy"]);
});