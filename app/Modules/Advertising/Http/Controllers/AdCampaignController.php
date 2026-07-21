<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Advertising\Application\Services\AdCampaignService;
use App\Modules\Advertising\Domain\Models\AdCampaign;
use App\Modules\Advertising\Http\Requests\SubmitAdCampaignRequest;
use App\Modules\Advertising\Http\Resources\AdCampaignResource;
use App\Modules\Feed\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdCampaignController extends Controller
{
    public function __construct(
        private readonly AdCampaignService $campaigns,
    ) {}

    public function store(SubmitAdCampaignRequest $request): JsonResponse
    {
        $user = $request->user();

        $campaign = $this->campaigns->submit(
            (int) $user->id,
            $request->postId(),
            $request->budget(),
            $request->days(),
        );

        return ApiResponse::success(AdCampaignResource::make($campaign), status: 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $campaigns = $this->campaigns->myCampaigns((int) $user->id);

        return ApiResponse::success(AdCampaignResource::collection($campaigns));
    }

    public function destroy(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        $user = $request->user();

        $campaign = $this->campaigns->cancel($adCampaign->id, (int) $user->id);

        return ApiResponse::success(AdCampaignResource::make($campaign));
    }

    public function eligiblePosts(Request $request): JsonResponse
    {
        $user = $request->user();

        $posts = $this->campaigns->eligiblePosts((int) $user->id);

        return ApiResponse::success(PostResource::collection($posts));
    }
}
