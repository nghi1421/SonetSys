<?php

use App\Core\Tenancy\Http\Controllers\TenantAdminController;
use App\Core\Tenancy\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

// No tenant.active here: a Super Admin must be able to reach and manage a
// suspended tenant (that's the whole point of these endpoints).
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('tenants', [TenantController::class, 'store']);

    Route::get('admin/tenants', [TenantAdminController::class, 'index']);
    Route::post('admin/tenants/{tenant}/suspend', [TenantAdminController::class, 'suspend']);
    Route::post('admin/tenants/{tenant}/reactivate', [TenantAdminController::class, 'reactivate']);
});
