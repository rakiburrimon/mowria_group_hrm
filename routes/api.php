<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PrivateDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Private Dashboard Routes
Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
    Route::get('/private', [PrivateDashboardController::class, 'index']);
    Route::get('/advanced', [App\Http\Controllers\Api\AdvancedDashboardController::class, 'index']);
});
