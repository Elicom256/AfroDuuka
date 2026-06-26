<?php

use App\Http\Controllers\ReorderRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ReorderRuleController::class, 'index'])->name('reorder-rules.index');
    Route::post('/', [ReorderRuleController::class, 'store'])->name('reorder-rules.store');
    Route::get('{reorderRule}', [ReorderRuleController::class, 'show'])->name('reorder-rules.show');
    Route::match(['put', 'patch'], '{reorderRule}', [ReorderRuleController::class, 'update'])->name('reorder-rules.update');
    Route::delete('{reorderRule}', [ReorderRuleController::class, 'destroy'])->name('reorder-rules.destroy');
});
