<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrivateDashboardController;
use App\Http\Controllers\Auth\LoginController;

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
    Route::get('/admin', [App\Http\Controllers\AdvancedDashboardController::class, 'index'])->name('dashboard.admin');
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
