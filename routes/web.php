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
