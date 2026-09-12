<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrivateDashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RoleController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Private Dashboard Routes (requires authentication)
Route::middleware('auth')->prefix('dashboard')->group(function () {
    Route::get('/private', [PrivateDashboardController::class, 'index'])->name('dashboard.private');
    Route::get('/admin', [App\Http\Controllers\AdvancedDashboardController::class, 'index'])
        ->middleware('permission:admin.dashboard')
        ->name('dashboard.admin');
});

// Employee Routes (requires authentication)
Route::middleware('auth')->prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    
    // Employee API endpoints
    Route::post('/{employee}/upload-profile-image', [EmployeeController::class, 'uploadProfileImage'])->name('employees.uploadProfileImage');
    Route::put('/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.updateStatus');
    Route::get('/statistics', [EmployeeController::class, 'statistics'])->name('employees.statistics');
});

// Leave Management Routes (requires authentication)
Route::middleware('auth')->prefix('leaves')->group(function () {
    Route::get('/', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/create', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/', [LeaveController::class, 'store'])->name('leaves.store');
    Route::get('/{leave}', [LeaveController::class, 'show'])->name('leaves.show');
    Route::get('/{leave}/edit', [LeaveController::class, 'edit'])->name('leaves.edit');
    Route::put('/{leave}', [LeaveController::class, 'update'])->name('leaves.update');
    Route::delete('/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
    
    // Admin approval panel
    Route::get('/approval-panel', [LeaveController::class, 'approvalPanel'])->name('leaves.approvalPanel');
    Route::post('/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    
    // Leave balance
    Route::get('/balance', [LeaveController::class, 'leaveBalance'])->name('leaves.balance');
});

// Attendance Management Routes (requires authentication)
Route::middleware('auth')->prefix('attendance')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    
    // Monthly view
    Route::get('/monthly', [AttendanceController::class, 'monthlyView'])->name('attendance.monthly');
    
    // Reports
    Route::get('/reports', [AttendanceController::class, 'reports'])->name('attendance.reports');
    
    // Check-in/Check-out
    Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkIn');
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkOut');
    
    // Statistics
    Route::get('/statistics', [AttendanceController::class, 'statistics'])->name('attendance.statistics');
});

// Activity Log (requires authentication + permission)
Route::middleware(['auth', 'permission:activity-logs.view'])->prefix('activity-logs')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/{activity}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
});

// Settings (requires authentication + permission)
Route::middleware(['auth', 'permission:settings.manage'])->prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/', [SettingController::class, 'update'])->name('settings.update');
});

// Departments (requires authentication + permission)
Route::middleware(['auth', 'permission:departments.manage'])->prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
});

// Roles & Permissions (requires authentication + permission)
Route::middleware(['auth', 'permission:roles.manage'])->prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});
