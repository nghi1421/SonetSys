<?php

use App\Modules\Story\Http\Controllers\StoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('stories', [StoryController::class, 'index']);
    Route::post('stories', [StoryController::class, 'store']);
    Route::delete('stories/{story}', [StoryController::class, 'destroy']);
    Route::post('stories/{story}/view', [StoryController::class, 'markViewed']);
    Route::get('stories/{story}/viewers', [StoryController::class, 'viewers']);
});
