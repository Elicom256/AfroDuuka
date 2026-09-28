<?php

use Illuminate\Support\Facades\Route;

Route::get("/up", function () {
    return response()->json([
        "status" => "ok",
        "timestamp" => now(),
    ]);
});

// SES event publishing, delivered by SNS.
//
// Intentionally public and outside every prefix group below: SNS is a server with no
// session and no tenant, so auth or branch scoping here would reject every genuine
// event. Authorisation is the RSA signature over the body, verified in the controller.
//
// Do not add this to a group, and do not add global auth middleware to api.php without
// carving this route out — an SNS signature is not a bearer token, and a webhook that
// 401s is indistinguishable from one that was never wired up.
Route::post("webhooks/ses", \App\Http\Controllers\Webhooks\SesWebhookController::class)
    ->name("webhooks.ses");

Route::prefix("users")->group(function () {
    require __DIR__."/users.php";
});

Route::prefix("products")->group(function () {
    require __DIR__."/products.php";
});

Route::prefix("sales")->group(function () {
    require __DIR__."/sales.php";
});

Route::prefix("dashboard")->group(function () {
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

Route::prefix("whatsapp")->group(function () {
    require __DIR__."/whatsapp.php";
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

Route::prefix("sale-orders")->group(function () {
    require __DIR__."/sale-orders.php";
});

Route::prefix("purchase-orders")->group(function () {
    require __DIR__."/purchase-orders.php";
});

Route::prefix("procurement")->group(function () {
    require __DIR__."/procurement.php";
});

Route::prefix("quotations")->group(function () {
    require __DIR__."/quotations.php";
});

Route::prefix("promotions")->group(function () {
    require __DIR__."/promotions.php";
});

Route::prefix("coupons")->group(function () {
    require __DIR__."/coupons.php";
});

Route::prefix("product-audits")->group(function () {
    require __DIR__."/product-audits.php";
});

Route::prefix("product-losses")->group(function () {
    require __DIR__."/product-losses.php";
});

Route::prefix("financial-audits")->group(function () {
    require __DIR__."/financial-audits.php";
});

Route::prefix("ai")->group(function () {
    require __DIR__."/ai.php";
});

Route::prefix("super-admin")->group(function () {
    require __DIR__."/super-admin.php";
});

Route::prefix("tax-categories")->group(function () {
    require __DIR__."/tax-categories.php";
});

Route::prefix("tax-rates")->group(function () {
    require __DIR__."/tax-rates.php";
});

Route::prefix("tax-payments")->group(function () {
    require __DIR__."/tax-payments.php";
});
