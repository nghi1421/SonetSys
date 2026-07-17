<?php

use App\Modules\Feed\Http\Controllers\CommentController;
use App\Modules\Feed\Http\Controllers\InteractionController;
use App\Modules\Feed\Http\Controllers\PostController;
use App\Modules\Feed\Http\Controllers\StickerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('stickers', [StickerController::class, 'index']);
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/following', [PostController::class, 'following']);
    Route::post('posts', [PostController::class, 'store']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::put('posts/{post}', [PostController::class, 'update']);
    Route::delete('posts/{post}', [PostController::class, 'destroy']);
    Route::post('posts/{post}/like', [InteractionController::class, 'togglePostLike']);

    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('comments/{comment}/like', [InteractionController::class, 'toggleCommentLike']);
});
