<?php

use App\Modules\Feed\Http\Controllers\Admin\ReactionTypeController as AdminReactionTypeController;
use App\Modules\Feed\Http\Controllers\CommentController;
use App\Modules\Feed\Http\Controllers\HashtagController;
use App\Modules\Feed\Http\Controllers\InteractionController;
use App\Modules\Feed\Http\Controllers\LocationSearchController;
use App\Modules\Feed\Http\Controllers\PostController;
use App\Modules\Feed\Http\Controllers\ReactionTypeController;
use App\Modules\Feed\Http\Controllers\ReelController;
use App\Modules\Feed\Http\Controllers\StickerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('stickers', [StickerController::class, 'index']);
    Route::get('locations/search', [LocationSearchController::class, 'search'])->middleware('throttle:20,1');
    Route::get('hashtags/{tag}/posts', [HashtagController::class, 'index']);
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/following', [PostController::class, 'following']);
    Route::post('posts', [PostController::class, 'store']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::put('posts/{post}', [PostController::class, 'update']);
    Route::delete('posts/{post}', [PostController::class, 'destroy']);
    Route::post('posts/{post}/like', [InteractionController::class, 'togglePostLike']);

    Route::get('reels', [ReelController::class, 'index']);
    Route::post('reels', [ReelController::class, 'store']);

    Route::get('reaction-types', [ReactionTypeController::class, 'index']);
    Route::get('admin/reaction-types', [AdminReactionTypeController::class, 'index']);
    Route::post('admin/reaction-types', [AdminReactionTypeController::class, 'store']);
    Route::put('admin/reaction-types/{reactionType}', [AdminReactionTypeController::class, 'update']);
    Route::delete('admin/reaction-types/{reactionType}', [AdminReactionTypeController::class, 'destroy']);

    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('comments/{comment}/like', [InteractionController::class, 'toggleCommentLike']);
});
