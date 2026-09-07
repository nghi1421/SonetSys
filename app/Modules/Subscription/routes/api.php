<?php

use App\Modules\Subscription\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Modules\Subscription\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('subscriptions/current', [SubscriptionController::class, 'show']);
    Route::post('subscriptions', [SubscriptionController::class, 'store']);
    Route::delete('subscriptions/current', [SubscriptionController::class, 'destroy']);

    Route::get('admin/subscriptions', [AdminSubscriptionController::class, 'index']);
    Route::post('admin/subscriptions/{userSubscription}/cancel', [AdminSubscriptionController::class, 'cancel']);
});
