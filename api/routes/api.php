<?php

use Illuminate\Support\Facades\Route;
Route::get("/up", function () {
    return response()->json([
        "status" => "ok",
        "timestamp" => now(),
    ]);
});

Route::get("/health", function () {
    $checks = [];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Exception $e) {
        $checks['database'] = 'error: ' . $e->getMessage();
    }

    try {
        $cache = Cache::store()->get('health_check');
        $checks['cache'] = 'ok';
    } catch (\Exception $e) {
        $checks['cache'] = 'error: ' . $e->getMessage();
    }

    $allOk = !str_contains(implode('', $checks), 'error');

    return response()->json([
        'status' => $allOk ? 'ok' : 'error',
        'timestamp' => now(),
        'checks' => $checks,
    ], $allOk ? 200 : 503);
});

Route::post("webhooks/ses", \App\Http\Controllers\Webhooks\SesWebhookController::class)
    ->name("webhooks.ses")
    ->middleware('throttle:webhook');

Route::middleware('throttle:api')->group(function () {
    Route::prefix("users")->group(function () {
        require __DIR__."/users.php";
    });

    Route::prefix("products")->group(function () {
        require __DIR__."/products.php";
    });

    Route::prefix("sales")->group(function () {
        require __DIR__."/sales.php";
    });

    Route::prefix("dashboard")->middleware('role')->group(function () {
        require __DIR__."/executive.php";
    });

    Route::prefix("purchases")->group(function () {
        require __DIR__."/purchases.php";
    });

    Route::prefix("returns")->group(function () {
        require __DIR__."/returns.php";
    });

    Route::prefix("settings")->middleware('role')->group(function () {
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

    Route::prefix("expenses")->middleware('role')->group(function () {
        require __DIR__."/expenses.php";
    });

    Route::prefix("stock-transfers")->group(function () {
        require __DIR__."/stock-transfers.php";
    });

    Route::prefix("reorder-rules")->group(function () {
        require __DIR__."/reorder-rules.php";
    });

    Route::prefix("report-exports")->group(function () {
        require __DIR__."/report-exports.php";
    });

    Route::prefix("price-history")->middleware('role')->group(function () {
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

    Route::prefix("product-audits")->middleware('role')->group(function () {
        require __DIR__."/product-audits.php";
    });

    Route::prefix("product-losses")->group(function () {
        require __DIR__."/product-losses.php";
    });

    Route::prefix("financial-audits")->middleware('role')->group(function () {
        require __DIR__."/financial-audits.php";
    });

    Route::prefix("ai")->group(function () {
        require __DIR__."/ai.php";
    });

    Route::prefix("super-admin")->group(function () {
        require __DIR__."/super-admin.php";
    });

    Route::prefix("tax-categories")->middleware('role')->group(function () {
        require __DIR__."/tax-categories.php";
    });

    Route::prefix("tax-rates")->middleware('role')->group(function () {
        require __DIR__."/tax-rates.php";
    });

    Route::prefix("tax-payments")->middleware('role')->group(function () {
        require __DIR__."/tax-payments.php";
    });

    Route::get("exports/{type}", [\App\Http\Controllers\ExportController::class, 'export'])->middleware('role');
});
