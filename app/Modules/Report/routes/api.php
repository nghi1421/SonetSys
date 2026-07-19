<?php

use App\Modules\Report\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Modules\Report\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('posts/{post}/report', [ReportController::class, 'reportPost']);
    Route::post('comments/{comment}/report', [ReportController::class, 'reportComment']);

    Route::get('admin/reports', [AdminReportController::class, 'index']);
    Route::post('admin/reports/{report}/resolve', [AdminReportController::class, 'resolve']);
    Route::post('admin/reports/{report}/dismiss', [AdminReportController::class, 'dismiss']);
});
