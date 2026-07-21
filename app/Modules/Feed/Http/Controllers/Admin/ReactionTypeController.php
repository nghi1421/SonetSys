<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers\Admin;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\ReactionTypeService;
use App\Modules\Feed\Domain\Models\ReactionType;
use App\Modules\Feed\Http\Requests\CreateReactionTypeRequest;
use App\Modules\Feed\Http\Requests\UpdateReactionTypeRequest;
use App\Modules\Feed\Http\Resources\ReactionTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ReactionTypeController extends Controller
{
    public function __construct(
        private readonly ReactionTypeService $reactionTypes,
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize(PermissionSlug::ReactionsManage->value);

        return ApiResponse::success(ReactionTypeResource::collection($this->reactionTypes->all()));
    }

    public function store(CreateReactionTypeRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::ReactionsManage->value);

        $reactionType = $this->reactionTypes->create($request->toDto(), (int) $request->user()->id);

        return ApiResponse::success(ReactionTypeResource::make($reactionType), status: 201);
    }

    public function update(UpdateReactionTypeRequest $request, ReactionType $reactionType): JsonResponse
    {
        Gate::authorize(PermissionSlug::ReactionsManage->value);

        $reactionType = $this->reactionTypes->update($reactionType, $request->toDto(), (int) $request->user()->id);

        return ApiResponse::success(ReactionTypeResource::make($reactionType));
    }

    public function destroy(Request $request, ReactionType $reactionType): JsonResponse
    {
        Gate::authorize(PermissionSlug::ReactionsManage->value);

        $this->reactionTypes->delete($reactionType);

        return ApiResponse::success();
    }
}
