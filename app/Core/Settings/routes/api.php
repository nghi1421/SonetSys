<?php

use App\Core\Settings\Http\Controllers\SystemSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('settings', [SystemSettingsController::class, 'show']);
    Route::put('settings', [SystemSettingsController::class, 'update']);
});
