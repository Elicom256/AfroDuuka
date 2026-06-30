<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth:sanctum')->group(function () {
//     Route::apiResource("", TodoController::class)->only(["index", "store", "show", "update", "destroy"]);
//     Route::get('/', [TodoController::class, 'index'])->name('todos.index');
//     Route::post('/', [TodoController::class, 'store'])->name('todos.store');
//     Route::get('{todo}', [TodoController::class, 'show'])->name('todos.show');
//     Route::match(['put', 'patch'], '{todo}', [TodoController::class, 'update'])->name('todos.update');
//     Route::delete('{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
// });
