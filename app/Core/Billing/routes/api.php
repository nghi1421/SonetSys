<?php

use App\Core\Billing\Http\Controllers\PlanController;
use App\Core\Billing\Http\Controllers\SubscriptionController;
use App\Core\Billing\Http\Controllers\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

// Public — pricing page + self-service signup, no auth required.
Route::get('plans', [PlanController::class, 'index']);
Route::post('tenant-registrations', [TenantRegistrationController::class, 'store'])
    ->middleware('throttle:tenant-registration');

// tenant.active NOT applied here: a suspended tenant's admin must still be
// able to view their subscription and self-recover by changing plan.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('subscription', [SubscriptionController::class, 'show']);
    Route::put('subscription', [SubscriptionController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'tenant.active'])->group(function (): void {
    Route::get('admin/plans', [PlanController::class, 'adminIndex']);
    Route::post('admin/plans', [PlanController::class, 'store']);
    Route::put('admin/plans/{plan}', [PlanController::class, 'update']);
    Route::delete('admin/plans/{plan}', [PlanController::class, 'destroy']);
});
