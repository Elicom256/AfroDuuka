<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| This file acts as the entry point for all API route modules.
| Each domain (auth, users, products, sales, etc.) is split into
| its own file for scalability.
|
*/
Route::prefix("auth")->group(function(){
  require __DIR__."/auth.php";
});
Route::prefix("users")->group(function(){
  require __DIR__.'/users.php';
});
Route::prefix("products")->group(function(){
  require __DIR__.'/products.php';
});
Route::prefix("sales")->group(function(){
  require __DIR__.'/sales.php';
});
Route::prefix("orders")->group(function(){
    require __DIR__.'/orders.php';
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
