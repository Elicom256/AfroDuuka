<?php

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

Route::prefix('reports')->group(function () {
    require __DIR__.'/reports.php';
});

Route::prefix("countries")->group(function () {
    Route::get('/', [\App\Http\Controllers\CountryController::class, 'index']);
});
