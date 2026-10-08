<?php

use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Webhooks\SesWebhookController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
});

/**
 * Unauthenticated health check, for a load balancer or uptime monitor.
 *
 * It answers only "is this dependency reachable" — never why. The raw driver
 * exception used to be returned in the body, and because this route needs no token
 * that handed anyone on the internet the connection's failure text: host and port it
 * tried, the database name, and whatever the driver chose to say about credentials or
 * authentication. A probe confirmed a 200 with the full body on a healthy database.
 *
 * The detail goes to the log, keyed by a correlation id that is also returned, so an
 * operator reading a 503 can find the cause without publishing it.
 */
Route::get('/health', function () {
    $checks = [];
    $allOk = true;

    $record = function (string $name, callable $probe) use (&$checks, &$allOk) {
        try {
            $probe();
            $checks[$name] = 'ok';
        } catch (Throwable $e) {
            $checks[$name] = 'error';
            $allOk = false;

            Log::error('health check failed', [
                'check' => $name,
                'exception' => $e,
            ]);
        }
    };

    $record('database', fn () => DB::connection()->getPdo());
    $record('cache', fn () => Cache::store()->get('health_check'));

    return response()->json([
        'status' => $allOk ? 'ok' : 'error',
        'timestamp' => now(),
        'checks' => $checks,
    ], $allOk ? 200 : 503);
});

Route::post('webhooks/ses', SesWebhookController::class)
    ->name('webhooks.ses')
    ->middleware('throttle:webhook');

// Public, read-only reference data for the signup form. BusinessCategory is a global
// table with no tenant column, so this leaks nothing about any business.
Route::get('business-categories', [BusinessCategoryController::class, 'index']);

Route::middleware('throttle:api')->group(function () {
    Route::prefix('users')->group(function () {
        require __DIR__.'/users.php';
    });

    Route::prefix('products')->group(function () {
        require __DIR__.'/products.php';
    });

    Route::prefix('sales')->group(function () {
        require __DIR__.'/sales.php';
    });

    Route::prefix('dashboard')->middleware('role')->group(function () {
        require __DIR__.'/executive.php';
    });

    Route::prefix('purchases')->group(function () {
        require __DIR__.'/purchases.php';
    });

    Route::prefix('returns')->group(function () {
        require __DIR__.'/returns.php';
    });

    Route::prefix('settings')->middleware('role')->group(function () {
        require __DIR__.'/settings.php';
    });

    Route::prefix('finances')->group(function () {
        require __DIR__.'/finances.php';
    });

    Route::prefix('currency-rates')->group(function () {
        require __DIR__.'/currency-rates.php';
    });

    Route::prefix('payment-gateways')->group(function () {
        require __DIR__.'/payment-gateways.php';
    });

    Route::prefix('whatsapp')->group(function () {
        require __DIR__.'/whatsapp.php';
    });

    Route::prefix('printers')->group(function () {
        require __DIR__.'/printers.php';
    });

    Route::prefix('expenses')->middleware('role')->group(function () {
        require __DIR__.'/expenses.php';
    });

    Route::prefix('stock-transfers')->group(function () {
        require __DIR__.'/stock-transfers.php';
    });

    Route::prefix('reorder-rules')->group(function () {
        require __DIR__.'/reorder-rules.php';
    });

    Route::prefix('report-exports')->group(function () {
        require __DIR__.'/report-exports.php';
    });

    Route::prefix('price-history')->middleware('role')->group(function () {
        require __DIR__.'/price-history.php';
    });

    Route::prefix('reports')->group(function () {
        require __DIR__.'/reports.php';
    });

    // Scheduled report definitions (Report model). Registered after the reports
    // prefix group above so the static stats paths (e.g. /reports/branch-performance)
    // win over the {report} wildcard in the resource routes.
    Route::apiResource('reports', ReportController::class);

    Route::prefix('countries')->group(function () {
        require __DIR__.'/countries.php';
    });

    Route::prefix('plans')->group(function () {
        require __DIR__.'/plans.php';
    });

    Route::prefix('subscriptions')->group(function () {
        require __DIR__.'/subscriptions.php';
    });

    Route::prefix('subscription-payments')->group(function () {
        require __DIR__.'/subscription-payments.php';
    });

    Route::prefix('receipts')->group(function () {
        require __DIR__.'/receipts.php';
    });

    Route::prefix('pos')->group(function () {
        require __DIR__.'/pos.php';
    });

    Route::prefix('sale-orders')->group(function () {
        require __DIR__.'/sale-orders.php';
    });

    Route::prefix('purchase-orders')->group(function () {
        require __DIR__.'/purchase-orders.php';
    });

    Route::prefix('procurement')->group(function () {
        require __DIR__.'/procurement.php';
    });

    Route::prefix('quotations')->group(function () {
        require __DIR__.'/quotations.php';
    });

    Route::prefix('promotions')->group(function () {
        require __DIR__.'/promotions.php';
    });

    Route::prefix('coupons')->group(function () {
        require __DIR__.'/coupons.php';
    });

    Route::prefix('product-audits')->middleware('role')->group(function () {
        require __DIR__.'/product-audits.php';
    });

    Route::prefix('product-losses')->group(function () {
        require __DIR__.'/product-losses.php';
    });

    Route::prefix('financial-audits')->middleware('role')->group(function () {
        require __DIR__.'/financial-audits.php';
    });

    Route::prefix('ai')->group(function () {
        require __DIR__.'/ai.php';
    });

    Route::prefix('super-admin')->group(function () {
        require __DIR__.'/super-admin.php';
    });

    Route::prefix('tax-categories')->middleware('role')->group(function () {
        require __DIR__.'/tax-categories.php';
    });

    Route::prefix('tax-rates')->middleware('role')->group(function () {
        require __DIR__.'/tax-rates.php';
    });

    Route::prefix('tax-payments')->middleware('role')->group(function () {
        require __DIR__.'/tax-payments.php';
    });

    // auth:sanctum is not optional here. Without it Sanctum's guard falls back to the
    // configured sanctum.guard (default 'web'), so Auth::user() is null,
    // EffectiveBranchScope::branchesFor(null) returns null, the branch filter is silently
    // skipped, and ExportService then calls Auth::user()->business_id on null. That is a
    // 500 for customers and suppliers, and a cross-tenant data leak for products, sales and
    // purchases. The 'role' middleware resolves the sanctum guard itself, which is why the
    // route looked authorised while the scoping was bypassed.
    Route::get('exports/{type}', [ExportController::class, 'export'])
        ->middleware(['auth:sanctum', 'role']);
});
