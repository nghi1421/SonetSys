<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\ReactionTypeService;
use App\Modules\Feed\Http\Resources\ReactionTypeResource;
use Illuminate\Http\JsonResponse;

final class ReactionTypeController extends Controller
{
    public function __construct(
        private readonly ReactionTypeService $reactionTypes,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(ReactionTypeResource::collection($this->reactionTypes->all()));
    }
}
