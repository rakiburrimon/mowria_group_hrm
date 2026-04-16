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

// Employee API Routes
Route::middleware('auth:sanctum')->prefix('employees')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\EmployeeController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\EmployeeController::class, 'store']);
    Route::get('/{employee}', [App\Http\Controllers\Api\EmployeeController::class, 'show']);
    Route::put('/{employee}', [App\Http\Controllers\Api\EmployeeController::class, 'update']);
    Route::delete('/{employee}', [App\Http\Controllers\Api\EmployeeController::class, 'destroy']);
    Route::post('/{employee}/upload-profile-image', [App\Http\Controllers\Api\EmployeeController::class, 'uploadProfileImage']);
    Route::put('/{employee}/status', [App\Http\Controllers\Api\EmployeeController::class, 'updateStatus']);
    Route::get('/statistics', [App\Http\Controllers\Api\EmployeeController::class, 'statistics']);
});
