<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Controllers\Admin;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use App\Modules\Wallet\Http\Requests\TopUpWalletRequest;
use App\Modules\Wallet\Http\Resources\AdminWalletResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::WalletManage->value);

        $paginator = $this->wallets->allWallets((int) $request->query('page', 1));

        return ApiResponse::success(AdminWalletResource::collection($paginator->items()), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function topup(TopUpWalletRequest $request, User $user): JsonResponse
    {
        Gate::authorize(PermissionSlug::WalletManage->value);

        $this->wallets->credit(
            $user->id,
            $request->amount(),
            WalletTransactionReason::AdminTopup,
            $request->note(),
            (int) $request->user()->id,
        );

        return ApiResponse::success([
            'balance' => $this->wallets->balanceFor($user->id),
        ]);
    }
}
