<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Infrastructure\Repositories;

use App\Modules\Advertising\Application\Contracts\AdCampaignRepositoryInterface;
use App\Modules\Advertising\Domain\Enums\AdCampaignStatus;
use App\Modules\Advertising\Domain\Models\AdCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentAdCampaignRepository implements AdCampaignRepositoryInterface
{
    public function create(array $attributes): AdCampaign
    {
        return AdCampaign::query()->create($attributes);
    }

    public function findById(int $id): ?AdCampaign
    {
        return AdCampaign::query()->find($id);
    }

    public function forAdvertiser(int $advertiserId): Collection
    {
        return AdCampaign::query()
            ->where('advertiser_id', $advertiserId)
            ->with('post')
            ->orderByDesc('id')
            ->get();
    }

    public function pendingPaginated(int $page, int $perPage): LengthAwarePaginator
    {
        return AdCampaign::query()
            ->where('status', AdCampaignStatus::Pending)
            ->with(['advertiser', 'post'])
            ->orderBy('id')
            ->paginate($perPage, page: $page);
    }

    public function updateStatus(AdCampaign $campaign, array $attributes): AdCampaign
    {
        $campaign->update($attributes);

        return $campaign->refresh();
    }

    public function activeForFeed(int $limit): Collection
    {
        return AdCampaign::query()
            ->where('status', AdCampaignStatus::Approved)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['post.author'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function expiredApproved(): Collection
    {
        return AdCampaign::query()
            ->where('status', AdCampaignStatus::Approved)
            ->where('ends_at', '<', now())
            ->get();
    }
}
