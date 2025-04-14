<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarkerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendefinisikan route-route API.
| Biasanya, API Anda akan menggunakan format JSON.
|
*/

// Middleware Sanctum untuk memastikan pengguna telah login
Route::middleware('auth:sanctum')->group(function () {
    // Route untuk CRUD marker
    Route::apiResource('markers', MarkerController::class);
});