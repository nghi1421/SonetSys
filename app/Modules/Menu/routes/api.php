<?php

use App\Modules\Menu\Http\Controllers\MenuItemController;
use App\Modules\Menu\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant.active'])->group(function (): void {
    Route::get('menu', [MenuItemController::class, 'index']);
    Route::post('menu', [MenuItemController::class, 'store']);
    Route::post('menu/reorder', [MenuItemController::class, 'reorder']);
    Route::get('menu/{menuItem:slug}', [MenuItemController::class, 'show']);
    Route::put('menu/{menuItem}', [MenuItemController::class, 'update']);
    Route::delete('menu/{menuItem}', [MenuItemController::class, 'destroy']);

    Route::get('pages', [StaticPageController::class, 'index']);
    Route::post('pages', [StaticPageController::class, 'store']);
    Route::put('pages/{page}', [StaticPageController::class, 'update']);
    Route::delete('pages/{page}', [StaticPageController::class, 'destroy']);
});
