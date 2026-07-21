<?php

use App\Modules\Chat\Http\Controllers\ConversationController;
use App\Modules\Chat\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/unread-count', [ConversationController::class, 'unreadCount']);
    Route::post('users/{user}/conversations', [ConversationController::class, 'store']);
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
});
