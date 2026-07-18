<?php

declare(strict_types=1);

namespace App\Modules\Block\Http\Controllers;

use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Block\Application\Services\BlockService;
use App\Modules\Block\Domain\Models\Block;
use App\Modules\Block\Http\Resources\BlockedUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlockController extends Controller
{
    public function __construct(
        private readonly BlockService $blocks,
    ) {}

    public function store(Request $request, User $user): JsonResponse
    {
        $this->blocks->block((int) $request->user()->id, $user->id);

        return ApiResponse::success();
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->blocks->unblock((int) $request->user()->id, $user->id);

        return ApiResponse::success();
    }

    public function index(Request $request): JsonResponse
    {
        $blockedUsers = $this->blocks->listBlocked((int) $request->user()->id)
            ->map(fn (Block $block) => $block->blocked);

        return ApiResponse::success(BlockedUserResource::collection($blockedUsers));
    }
}
