<?php

use App\Core\Tenancy\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('tenants', [TenantController::class, 'store']);
});
