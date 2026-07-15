<?php

use App\Modules\Group\Http\Controllers\GroupController;
use App\Modules\Group\Http\Controllers\GroupMembershipController;
use App\Modules\Group\Http\Controllers\GroupPostController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('groups', [GroupController::class, 'index']);
    Route::post('groups', [GroupController::class, 'store']);
    Route::get('groups/{group:slug}', [GroupController::class, 'show']);
    Route::put('groups/{group}', [GroupController::class, 'update']);
    Route::delete('groups/{group}', [GroupController::class, 'destroy']);

    Route::post('groups/{group}/join', [GroupMembershipController::class, 'join']);
    Route::post('groups/{group}/leave', [GroupMembershipController::class, 'leave']);
    Route::get('groups/{group}/members', [GroupMembershipController::class, 'members']);
    Route::get('groups/{group}/requests', [GroupMembershipController::class, 'requests']);
    Route::post('groups/{group}/requests/{user}/approve', [GroupMembershipController::class, 'approve']);
    Route::delete('groups/{group}/members/{user}', [GroupMembershipController::class, 'remove']);

    Route::get('groups/{group}/posts', [GroupPostController::class, 'index']);
    Route::post('groups/{group}/posts', [GroupPostController::class, 'store']);
});
