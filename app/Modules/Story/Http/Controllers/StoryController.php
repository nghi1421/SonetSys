<?php

declare(strict_types=1);

namespace App\Modules\Story\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Story\Application\Services\StoryService;
use App\Modules\Story\Domain\Models\Story;
use App\Modules\Story\Http\Requests\CreateStoryRequest;
use App\Modules\Story\Http\Resources\StoryResource;
use App\Modules\Story\Http\Resources\StoryViewerResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class StoryController extends Controller
{
    public function __construct(
        private readonly StoryService $stories,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $stories = $this->stories->listActiveFeed((int) $request->user()->id);

        $groups = $stories->groupBy('author_id')->map(fn (Collection $group) => [
            'author' => [
                'id' => $group->first()->author->id,
                'name' => $group->first()->author->name,
            ],
            'stories' => StoryResource::collection($group->values()),
        ])->values();

        return ApiResponse::success($groups);
    }

    public function store(CreateStoryRequest $request): JsonResponse
    {
        $story = $this->stories->create($request->toDto());

        return ApiResponse::success(StoryResource::make($story->load('author')), status: 201);
    }

    public function destroy(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();

        $isAuthor = $story->author_id === $user->id;
        $canModerate = $user->hasPermission(PermissionSlug::PostsDeleteAny->value);

        if (! $isAuthor && ! $canModerate) {
            throw new AuthorizationException('You cannot delete this story.');
        }

        $this->stories->delete($story);

        return ApiResponse::success();
    }

    public function markViewed(Request $request, Story $story): JsonResponse
    {
        $this->stories->recordView($story, (int) $request->user()->id);

        return ApiResponse::success();
    }

    public function viewers(Request $request, Story $story): JsonResponse
    {
        if ($story->author_id !== $request->user()->id) {
            throw new AuthorizationException('Only the story owner can view this list.');
        }

        return ApiResponse::success(StoryViewerResource::collection($this->stories->viewers($story)));
    }
}
