<?php

use App\Http\Controllers\CountryController;
use Illuminate\Support\Facades\Route;

Route::get("/", [CountryController::class, "index"]);
