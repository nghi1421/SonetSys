<?php

use App\Modules\Wallet\Http\Controllers\Admin\WalletController as AdminWalletController;
use App\Modules\Wallet\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('wallet', [WalletController::class, 'show']);
    Route::get('wallet/transactions', [WalletController::class, 'transactions']);

    Route::get('admin/wallets', [AdminWalletController::class, 'index']);
    Route::post('admin/users/{user}/wallet/topup', [AdminWalletController::class, 'topup']);
});
