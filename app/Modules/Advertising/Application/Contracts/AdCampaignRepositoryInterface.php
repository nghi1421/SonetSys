<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Application\Contracts;

use App\Modules\Advertising\Domain\Models\AdCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdCampaignRepositoryInterface
{
    public function create(array $attributes): AdCampaign;

    public function findById(int $id): ?AdCampaign;

    /**
     * @return Collection<int, AdCampaign>
     */
    public function forAdvertiser(int $advertiserId): Collection;

    public function pendingPaginated(int $page, int $perPage): LengthAwarePaginator;

    public function updateStatus(AdCampaign $campaign, array $attributes): AdCampaign;

    /**
     * @return Collection<int, AdCampaign>
     */
    public function activeForFeed(int $limit): Collection;

    /**
     * @return Collection<int, AdCampaign>
     */
    public function expiredApproved(): Collection;
}
