<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Http\Resources\WalletTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'balance' => $this->wallets->balanceFor((int) $request->user()->id),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $paginator = $this->wallets->transactionsForUser(
            (int) $request->user()->id,
            (int) $request->query('page', 1),
        );

        return ApiResponse::success(WalletTransactionResource::collection($paginator->items()), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }
}
