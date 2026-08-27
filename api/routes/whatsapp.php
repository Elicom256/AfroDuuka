<?php

use App\Http\Controllers\WhatsAppConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [WhatsAppConfigController::class, 'index']);
    Route::post('/', [WhatsAppConfigController::class, 'store']);
    Route::put('/{whatsAppConfig}', [WhatsAppConfigController::class, 'update']);
    Route::post('/test-message', [WhatsAppConfigController::class, 'testMessage']);
    Route::get('/templates', [WhatsAppConfigController::class, 'templates']);
    Route::get('/logs', [WhatsAppConfigController::class, 'logs']);
});
