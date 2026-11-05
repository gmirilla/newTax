<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Basic auth routes. Replace with Laravel Breeze package in production:
| composer require laravel/breeze && php artisan breeze:install blade
*/

Route::middleware('guest')->group(function () {
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Branded company login (custom URL, e.g. /acme-ltd/login)
    Route::get('/{tenant:slug}/login',  [AuthController::class, 'showTenantLogin'])->name('login.tenant');
    Route::post('/{tenant:slug}/login', [AuthController::class, 'tenantLogin'])->name('login.tenant.submit')->middleware('throttle:10,1');

    // ── Password reset ──────────────────────────────────────────────────────
    Route::get('/forgot-password',  [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',  [PasswordResetController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Email verification ────────────────────────────────────────────────────
    Route::get('/email/verify',           [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])->name('verification.verify')->middleware('signed');
    Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])->name('verification.send')->middleware('throttle:6,1');
});
