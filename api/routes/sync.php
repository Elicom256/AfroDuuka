<?php

use App\Http\Controllers\SyncBatchController;
use Illuminate\Support\Facades\Route;

/**
 * Offline synchronization surface.
 *
 * auth:sanctum is mandatory: offline operations must still authenticate when they
 * reach the server (RequireBusiness runs globally and rejects callers with no
 * tenant). RequireTillAccess is deliberately NOT applied — a replayed operation was
 * already authorised on the device that created it, and re-opening a till hours
 * later to flush a backlog would block legitimate syncs. Per-type authority
 * (till for sales, stock modification for inventory, policies for customers) is
 * enforced by the handlers themselves.
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/batch', [SyncBatchController::class, 'store']);
});
