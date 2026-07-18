<?php

use App\Http\Controllers\CountryController;
use Illuminate\Support\Facades\Route;

// Application health check

Route::get('/up', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| This file acts as the entry point for all API route modules.
| Each domain (auth, users, products, sales, etc.) is split into
| its own file for scalability.
|
*/
Route::prefix("users")->group(function(){
  require __DIR__.'/users.php';
});
Route::prefix("products")->group(function(){
  require __DIR__.'/products.php';
});
Route::prefix("sales")->group(function(){
  require __DIR__.'/sales.php';
});
Route::prefix("admin")->group(function(){
    require __DIR__.'/admin.php';
});

Route::prefix("purchases")->group(function(){
    require __DIR__.'/purchases.php';
});

Route::prefix("returns")->group(function () {
    require __DIR__.'/returns.php';
});

Route::prefix("settings")->group(function(){
  require __DIR__.'/settings.php';
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
    require __DIR__.'/price-history.php';
});

Route::prefix('reports')->group(function () {
    require __DIR__.'/reports.php';
});

Route::prefix("countries")->group(function () {
    Route::get('/', [CountryController::class, 'index']);
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
    require __DIR__.'/receipts.php';
});

Route::prefix("pos")->group(function () {
    require __DIR__.'/pos.php';
});

Route::prefix("orders")->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\OrderController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\OrderController::class, 'store']);
    Route::get('/{order}', [\App\Http\Controllers\OrderController::class, 'show']);
    Route::put('/{order}', [\App\Http\Controllers\OrderController::class, 'update']);
    Route::delete('/{order}', [\App\Http\Controllers\OrderController::class, 'destroy']);
});

Route::prefix("promotions")->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\PromotionController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\PromotionController::class, 'store']);
    Route::get('/{promotion}', [\App\Http\Controllers\PromotionController::class, 'show']);
    Route::put('/{promotion}', [\App\Http\Controllers\PromotionController::class, 'update']);
    Route::delete('/{promotion}', [\App\Http\Controllers\PromotionController::class, 'destroy']);
});

Route::prefix("coupons")->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\CouponController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\CouponController::class, 'store']);
    Route::get('/{coupon}', [\App\Http\Controllers\CouponController::class, 'show']);
    Route::put('/{coupon}', [\App\Http\Controllers\CouponController::class, 'update']);
    Route::delete('/{coupon}', [\App\Http\Controllers\CouponController::class, 'destroy']);
});

Route::prefix("ai")->middleware('auth:sanctum')->group(function () {
    Route::post('/chat', [\App\Http\Controllers\AiController::class, 'chat']);
    Route::get('/tools', [\App\Http\Controllers\AiController::class, 'tools']);
});

Route::prefix("super-admin")->middleware('auth:sanctum')->group(function () {
    Route::get('/businesses', [\App\Http\Controllers\SuperAdminBusinessController::class, 'index']);
    Route::get('/businesses/{business}', [\App\Http\Controllers\SuperAdminBusinessController::class, 'show']);
    Route::patch('/businesses/{business}/status', [\App\Http\Controllers\SuperAdminBusinessController::class, 'updateStatus']);
});
