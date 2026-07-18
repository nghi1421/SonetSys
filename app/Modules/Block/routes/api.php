<?php

use App\Modules\Block\Http\Controllers\BlockController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('blocked-users', [BlockController::class, 'index']);
    Route::post('users/{user}/block', [BlockController::class, 'store']);
    Route::delete('users/{user}/block', [BlockController::class, 'destroy']);
});
