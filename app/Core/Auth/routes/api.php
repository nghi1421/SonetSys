<?php

use App\Core\Auth\Http\Controllers\AuthController;
use App\Core\Auth\Http\Controllers\PasswordResetController;
use App\Core\Auth\Http\Controllers\ProfileController;
use App\Core\Auth\Http\Controllers\UserController;
use App\Core\Auth\Http\Controllers\UserSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('throttle:password-reset')->group(function (): void {
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink']);
        Route::post('reset-password', [PasswordResetController::class, 'reset']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/search', [UserSearchController::class, 'search'])->middleware('throttle:20,1');
    Route::get('users/recent', [UserSearchController::class, 'recent']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::post('profile', [ProfileController::class, 'update']);
});
