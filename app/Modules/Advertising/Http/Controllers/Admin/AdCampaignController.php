<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Http\Controllers\Admin;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Advertising\Application\Services\AdCampaignService;
use App\Modules\Advertising\Domain\Models\AdCampaign;
use App\Modules\Advertising\Http\Requests\RejectAdCampaignRequest;
use App\Modules\Advertising\Http\Resources\AdminAdCampaignResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AdCampaignController extends Controller
{
    public function __construct(
        private readonly AdCampaignService $campaigns,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::AdsReview->value);

        $paginator = $this->campaigns->pendingQueue((int) $request->query('page', 1));

        return ApiResponse::success(AdminAdCampaignResource::collection($paginator->items()), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function approve(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        Gate::authorize(PermissionSlug::AdsReview->value);

        $user = $request->user();

        $campaign = $this->campaigns->approve($adCampaign->id, (int) $user->id);

        return ApiResponse::success(AdminAdCampaignResource::make($campaign));
    }

    public function reject(RejectAdCampaignRequest $request, AdCampaign $adCampaign): JsonResponse
    {
        Gate::authorize(PermissionSlug::AdsReview->value);

        $user = $request->user();

        $campaign = $this->campaigns->reject($adCampaign->id, (int) $user->id, $request->reason());

        return ApiResponse::success(AdminAdCampaignResource::make($campaign));
    }
}
