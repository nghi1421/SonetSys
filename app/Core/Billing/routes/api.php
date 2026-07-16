<?php

use App\Core\Billing\Http\Controllers\PlanController;
use App\Core\Billing\Http\Controllers\SubscriptionController;
use App\Core\Billing\Http\Controllers\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

// Public — pricing page + self-service signup, no auth required.
Route::get('plans', [PlanController::class, 'index']);
Route::post('tenant-registrations', [TenantRegistrationController::class, 'store']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('admin/plans', [PlanController::class, 'adminIndex']);
    Route::post('admin/plans', [PlanController::class, 'store']);
    Route::put('admin/plans/{plan}', [PlanController::class, 'update']);
    Route::delete('admin/plans/{plan}', [PlanController::class, 'destroy']);

    Route::get('subscription', [SubscriptionController::class, 'show']);
});
