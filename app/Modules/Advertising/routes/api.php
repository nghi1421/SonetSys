<?php

use App\Modules\Advertising\Http\Controllers\AdCampaignController;
use App\Modules\Advertising\Http\Controllers\Admin\AdCampaignController as AdminAdCampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('ads/eligible-posts', [AdCampaignController::class, 'eligiblePosts']);
    Route::get('ads/campaigns', [AdCampaignController::class, 'index']);
    Route::post('ads/campaigns', [AdCampaignController::class, 'store']);
    Route::delete('ads/campaigns/{adCampaign}', [AdCampaignController::class, 'destroy']);

    Route::get('admin/ads', [AdminAdCampaignController::class, 'index']);
    Route::post('admin/ads/{adCampaign}/approve', [AdminAdCampaignController::class, 'approve']);
    Route::post('admin/ads/{adCampaign}/reject', [AdminAdCampaignController::class, 'reject']);
});
