<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
    
    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        // e.g. Route::get('/dashboard', [AdminController::class, 'index']);
    });

    // Seller routes
    Route::middleware('seller')->prefix('seller')->group(function () {
        // e.g. Route::get('/dashboard', [SellerController::class, 'index']);
    });

    // Support routes
    Route::middleware('support')->prefix('support')->group(function () {
        // support routes here
    });
});
