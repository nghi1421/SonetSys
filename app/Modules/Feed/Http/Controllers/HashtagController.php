<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class HashtagController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function index(Request $request, string $tag): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->query('limit', 20), 50);

        $result = $this->posts->feedForHashtag(
            Str::lower($tag),
            $user->id,
            $request->query('cursor'),
            $limit,
        );

        return ApiResponse::success(PostResource::collection($result['items']), [
            'next_cursor' => $result['next_cursor'],
        ]);
    }
}
