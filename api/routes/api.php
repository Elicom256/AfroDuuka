<?php

use Illuminate\Support\Facades\Route;

Route::get("/up", function () {
    return response()->json([
        "status" => "ok",
        "timestamp" => now(),
    ]);
});

Route::prefix("users")->group(function () {
    require __DIR__."/users.php";
});

Route::prefix("products")->group(function () {
    require __DIR__."/products.php";
});

Route::prefix("sales")->group(function () {
    require __DIR__."/sales.php";
});

Route::prefix("admin")->group(function () {
    require __DIR__."/admin.php";
});

Route::prefix("purchases")->group(function () {
    require __DIR__."/purchases.php";
});

Route::prefix("returns")->group(function () {
    require __DIR__."/returns.php";
});

Route::prefix("settings")->group(function () {
    require __DIR__."/settings.php";
});

Route::prefix("finances")->group(function () {
    require __DIR__."/finances.php";
});

Route::prefix("currency-rates")->group(function () {
    require __DIR__."/currency-rates.php";
});

Route::prefix("payment-gateways")->group(function () {
    require __DIR__."/payment-gateways.php";
});

Route::prefix("printers")->group(function () {
    require __DIR__."/printers.php";
});

Route::prefix("expenses")->group(function () {
    require __DIR__."/expenses.php";
});

Route::prefix("stock-transfers")->group(function () {
    require __DIR__."/stock-transfers.php";
});

Route::prefix("reorder-rules")->group(function () {
    require __DIR__."/reorder-rules.php";
});

Route::prefix("tax-invoices")->group(function () {
    require __DIR__."/tax-invoices.php";
});

Route::prefix("loyalty")->group(function () {
    require __DIR__."/loyalty.php";
});

Route::prefix("report-exports")->group(function () {
    require __DIR__."/report-exports.php";
});

Route::prefix("price-history")->group(function () {
    require __DIR__."/price-history.php";
});

Route::prefix("reports")->group(function () {
    require __DIR__."/reports.php";
});

Route::prefix("countries")->group(function () {
    require __DIR__."/countries.php";
});

Route::prefix("plans")->group(function () {
    require __DIR__."/plans.php";
});

Route::prefix("subscriptions")->group(function () {
    require __DIR__."/subscriptions.php";
});

Route::prefix("subscription-payments")->group(function () {
    require __DIR__."/subscription-payments.php";
});

Route::prefix("receipts")->group(function () {
    require __DIR__."/receipts.php";
});

Route::prefix("pos")->group(function () {
    require __DIR__."/pos.php";
});

Route::prefix("orders")->group(function () {
    require __DIR__."/orders.php";
});

Route::prefix("promotions")->group(function () {
    require __DIR__."/promotions.php";
});

Route::prefix("coupons")->group(function () {
    require __DIR__."/coupons.php";
});

Route::prefix("ai")->group(function () {
    require __DIR__."/ai.php";
});

Route::prefix("super-admin")->group(function () {
    require __DIR__."/super-admin.php";
});
