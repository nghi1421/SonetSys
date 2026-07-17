<?php

use App\Modules\Follow\Http\Controllers\FollowController;
use App\Modules\Follow\Http\Controllers\UserPostController;
use App\Modules\Follow\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('users/{user}/profile', [UserProfileController::class, 'show']);
    Route::get('users/{user}/posts', [UserPostController::class, 'index']);
    Route::post('users/{user}/follow', [FollowController::class, 'store']);
    Route::delete('users/{user}/follow', [FollowController::class, 'destroy']);
    Route::get('users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('users/{user}/following', [FollowController::class, 'following']);
});
