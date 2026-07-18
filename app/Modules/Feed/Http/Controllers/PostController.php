<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Advertising\Application\Services\AdCampaignService;
use App\Modules\Feed\Application\Contracts\GroupAccessCheckerInterface;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Feed\Http\Requests\CreatePostRequest;
use App\Modules\Feed\Http\Requests\UpdatePostRequest;
use App\Modules\Feed\Http\Resources\PostResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
        private readonly GroupAccessCheckerInterface $groupAccess,
        private readonly AdCampaignService $ads,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->query('limit', 20), 50);
        $cursor = $request->query('cursor');

        $result = $this->posts->feed(
            $user->id,
            $cursor,
            $limit,
        );

        // Sponsored posts are injected only on the first page load — the
        // cursor is null exactly once per feed session, so a boosted post
        // never reappears duplicated as the viewer scrolls further pages.
        if ($cursor === null) {
            $existingIds = $result['items']->pluck('id')->all();

            $sponsored = $this->ads->activeForFeed(2)
                ->reject(fn (Post $post) => in_array($post->id, $existingIds, true));

            $result['items'] = $sponsored->concat($result['items'])->values();
        }

        return ApiResponse::success(PostResource::collection($result['items']), [
            'next_cursor' => $result['next_cursor'],
        ]);
    }

    public function following(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->query('limit', 20), 50);

        $result = $this->posts->feedForFollowing(
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

        return ApiResponse::success(PostResource::make($post->load(['author', 'sharedPost.author'])), status: 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->visibility === PostVisibility::Private && $post->author_id !== $user->id) {
            throw new ModelNotFoundException;
        }

        if ($post->group_id !== null && ! $this->groupAccess->canView($post->group_id, (int) $user->id)) {
            throw new AuthorizationException('You must be a member of this group.');
        }

        $this->posts->markLikedByViewer($post, $user->id);

        return ApiResponse::success(PostResource::make($post->load(['author', 'sharedPost.author'])));
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->author_id !== $user->id) {
            throw new AuthorizationException('You can only edit your own posts.');
        }

        $post = $this->posts->update($post, $request->toDto($post));

        return ApiResponse::success(PostResource::make($post->load(['author', 'sharedPost.author'])));
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $isAuthor = $post->author_id === $user->id;
        $canModerate = $user->hasPermission(PermissionSlug::PostsDeleteAny->value);

        if (! $isAuthor && ! $canModerate) {
            throw new AuthorizationException('You cannot delete this post.');
        }

        $this->posts->delete($post);

        return ApiResponse::success();
    }
}
