<?php

declare(strict_types=1);

namespace App\Modules\Report\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Report\Application\Services\ReportService;
use App\Modules\Report\Http\Requests\SubmitReportRequest;
use App\Modules\Report\Http\Resources\ReportResource;
use Illuminate\Http\JsonResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function reportPost(SubmitReportRequest $request, Post $post): JsonResponse
    {
        $report = $this->reports->submit(
            (int) $request->user()->id,
            'post',
            $post->id,
            $request->reason(),
            $request->details(),
        );

        return ApiResponse::success(ReportResource::make($report), status: 201);
    }

    public function reportComment(SubmitReportRequest $request, Comment $comment): JsonResponse
    {
        $report = $this->reports->submit(
            (int) $request->user()->id,
            'comment',
            $comment->id,
            $request->reason(),
            $request->details(),
        );

        return ApiResponse::success(ReportResource::make($report), status: 201);
    }
}
