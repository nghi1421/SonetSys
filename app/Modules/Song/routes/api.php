<?php

use App\Modules\Song\Http\Controllers\Admin\SongController as AdminSongController;
use App\Modules\Song\Http\Controllers\SongController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('songs', [SongController::class, 'index']);

    Route::get('admin/songs', [AdminSongController::class, 'index']);
    Route::post('admin/songs', [AdminSongController::class, 'store']);
    Route::put('admin/songs/{song}', [AdminSongController::class, 'update']);
    Route::delete('admin/songs/{song}', [AdminSongController::class, 'destroy']);
});
