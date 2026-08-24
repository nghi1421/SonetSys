<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Http\Requests\CreateReelRequest;
use App\Modules\Feed\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReelController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->query('limit', 20), 50);

        $result = $this->posts->reelsFeed(
            $user->id,
            $request->query('cursor'),
            $limit,
        );

        return ApiResponse::success(PostResource::collection($result['items']), [
            'next_cursor' => $result['next_cursor'],
        ]);
    }

    public function store(CreateReelRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->toDto());

        return ApiResponse::success(PostResource::make($post->load(['author', 'hashtags', 'mentions', 'song'])), status: 201);
    }
}
