<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\CommentService;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Feed\Http\Requests\CreateCommentRequest;
use App\Modules\Feed\Http\Resources\CommentResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $comments,
    ) {}

    public function index(Request $request, Post $post): JsonResponse
    {
        $this->ensureSameTenant($request, $post);

        return ApiResponse::success(CommentResource::collection($this->comments->listForPost($post->id)));
    }

    public function store(CreateCommentRequest $request, Post $post): JsonResponse
    {
        $this->ensureSameTenant($request, $post);

        $comment = $this->comments->create($request->toDto());

        return ApiResponse::success(CommentResource::make($comment->load('author')), status: 201);
    }

    private function ensureSameTenant(Request $request, Post $post): void
    {
        if ($post->tenant_id !== $request->user()->tenant_id) {
            throw new ModelNotFoundException;
        }
    }
}
