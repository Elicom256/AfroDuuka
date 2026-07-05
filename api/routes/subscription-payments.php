<?php

use App\Http\Controllers\SubscriptionPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [SubscriptionPaymentController::class, 'index'])->name('subscription-payments.index');
    Route::post('/', [SubscriptionPaymentController::class, 'store'])->name('subscription-payments.store');
    Route::get('{subscriptionPayment}', [SubscriptionPaymentController::class, 'show'])->name('subscription-payments.show');
    Route::match(['put', 'patch'], '{subscriptionPayment}', [SubscriptionPaymentController::class, 'update'])->name('subscription-payments.update');
    Route::delete('{subscriptionPayment}', [SubscriptionPaymentController::class, 'destroy'])->name('subscription-payments.destroy');
});
