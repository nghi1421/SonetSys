<?php

use App\Core\Storage\Http\Controllers\MediaController;
use App\Core\Storage\Http\Controllers\StorageSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.active'])->group(function (): void {
    Route::get('storage/settings', [StorageSettingsController::class, 'show']);
    Route::put('storage/settings', [StorageSettingsController::class, 'update']);

    Route::get('media', [MediaController::class, 'index']);
    Route::delete('media/{media}', [MediaController::class, 'destroy']);
});
