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

// Leave Management API Routes
Route::middleware('auth:sanctum')->prefix('leaves')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\LeaveController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\LeaveController::class, 'store']);
    Route::get('/{leave}', [App\Http\Controllers\Api\LeaveController::class, 'show']);
    Route::put('/{leave}', [App\Http\Controllers\Api\LeaveController::class, 'update']);
    Route::delete('/{leave}', [App\Http\Controllers\Api\LeaveController::class, 'destroy']);
    
    // Approval panel
    Route::get('/approval-panel', [App\Http\Controllers\Api\LeaveController::class, 'approvalPanel']);
    Route::post('/{leave}/approve', [App\Http\Controllers\Api\LeaveController::class, 'approve']);
    
    // Leave balance
    Route::get('/balance', [App\Http\Controllers\Api\LeaveController::class, 'leaveBalance']);
    
    // Statistics
    Route::get('/statistics', [App\Http\Controllers\Api\LeaveController::class, 'statistics']);
});

// Attendance Management API Routes
Route::middleware('auth:sanctum')->prefix('attendance')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\AttendanceController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\AttendanceController::class, 'store']);
    Route::get('/{attendance}', [App\Http\Controllers\Api\AttendanceController::class, 'show']);
    Route::put('/{attendance}', [App\Http\Controllers\Api\AttendanceController::class, 'update']);
    Route::delete('/{attendance}', [App\Http\Controllers\Api\AttendanceController::class, 'destroy']);
    
    // Monthly view
    Route::get('/monthly', [App\Http\Controllers\Api\AttendanceController::class, 'monthlyView']);
    
    // Reports
    Route::get('/reports', [App\Http\Controllers\Api\AttendanceController::class, 'reports']);
    
    // Check-in/Check-out
    Route::post('/check-in', [App\Http\Controllers\Api\AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [App\Http\Controllers\Api\AttendanceController::class, 'checkOut']);
    
    // Statistics
    Route::get('/statistics', [App\Http\Controllers\Api\AttendanceController::class, 'statistics']);
    
    // Employee summary
    Route::get('/employee-summary', [App\Http\Controllers\Api\AttendanceController::class, 'employeeSummary']);
});
