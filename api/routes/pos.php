<?php

use App\Http\Controllers\PosController;
use App\Http\Middleware\RequireTillAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    /**
     * Staff only.
     *
     * Both counterparties — customer and supplier — hold a User row carrying the
     * tenant's business_id, so RequireBusiness waved them through and, with no role check
     * here, they reached the till. See RequireTillAccess for why that is authority a
     * counterparty should not hold.
     *
     * The whole surface is covered by one declaration rather than a check per method,
     * which also covers the search endpoints: a catalogue search is how an attacker
     * prices a target's stock before deciding what is worth taking.
     */
    Route::middleware(RequireTillAccess::class)->group(function () {
        Route::get('/products/search', [PosController::class, 'searchProducts']);
        Route::get('/products/by-barcode/{barcode}', [PosController::class, 'byBarcode']);
        Route::get('/customers/search', [PosController::class, 'searchCustomers']);

        Route::post('/cart/validate', [PosController::class, 'validateCart']);
        Route::post('/checkout', [PosController::class, 'checkout']);

        Route::post('/sales/hold', [PosController::class, 'holdSale']);
        Route::get('/sales/held', [PosController::class, 'getHeldSales']);
        Route::get('/sales/held/{id}', [PosController::class, 'resumeHeldSale']);
        Route::delete('/sales/held/{id}', [PosController::class, 'deleteHeldSale']);
    });
});