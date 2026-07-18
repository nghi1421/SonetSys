<?php

declare(strict_types=1);

namespace App\Modules\Report\Http\Controllers\Admin;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Report\Application\Services\ReportService;
use App\Modules\Report\Domain\Models\Report;
use App\Modules\Report\Http\Resources\AdminReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::ReportsReview->value);

        $paginator = $this->reports->pendingQueue((int) $request->query('page', 1));

        return ApiResponse::success(AdminReportResource::collection($paginator->items()), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function resolve(Request $request, Report $report): JsonResponse
    {
        Gate::authorize(PermissionSlug::ReportsReview->value);

        $resolved = $this->reports->resolve($report->id, (int) $request->user()->id);

        return ApiResponse::success(AdminReportResource::make($resolved));
    }

    public function dismiss(Request $request, Report $report): JsonResponse
    {
        Gate::authorize(PermissionSlug::ReportsReview->value);

        $dismissed = $this->reports->dismiss($report->id, (int) $request->user()->id);

        return ApiResponse::success(AdminReportResource::make($dismissed));
    }
}
