<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Feed\Http\Requests\CreatePostRequest;
use App\Modules\Feed\Http\Resources\PostResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->query('limit', 20), 50);

        $result = $this->posts->feedForTenant(
            $user->tenant_id,
            $user->id,
            $request->query('cursor'),
            $limit,
        );

        return ApiResponse::success(PostResource::collection($result['items']), [
            'next_cursor' => $result['next_cursor'],
        ]);
    }

    public function store(CreatePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->toDto());

        return ApiResponse::success(PostResource::make($post->load('author')), status: 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->tenant_id !== $user->tenant_id) {
            throw new ModelNotFoundException;
        }

        if ($post->visibility === PostVisibility::Private && $post->author_id !== $user->id) {
            throw new ModelNotFoundException;
        }

        return ApiResponse::success(PostResource::make($post->load('author')));
    }
}
