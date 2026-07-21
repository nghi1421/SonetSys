<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Application\Services;

use App\Modules\Advertising\Application\Contracts\AdCampaignRepositoryInterface;
use App\Modules\Advertising\Domain\Enums\AdCampaignStatus;
use App\Modules\Advertising\Domain\Models\AdCampaign;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdCampaignService
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly AdCampaignRepositoryInterface $campaigns,
        private readonly WalletService $wallet,
    ) {}

    public function submit(int $advertiserId, int $postId, int $budget, int $days): AdCampaign
    {
        $post = Post::query()->find($postId);

        if ($post === null
            || $post->author_id !== $advertiserId
            || $post->visibility !== PostVisibility::Public
            || $post->group_id !== null
        ) {
            throw ValidationException::withMessages([
                'post' => 'This post is not eligible to be boosted.',
            ]);
        }

        if ($budget <= 0) {
            throw ValidationException::withMessages([
                'budget' => 'Budget must be greater than zero.',
            ]);
        }

        if ($days <= 0) {
            throw ValidationException::withMessages([
                'days' => 'Duration must be at least one day.',
            ]);
        }

        return DB::transaction(function () use ($advertiserId, $postId, $budget, $days): AdCampaign {
            $this->wallet->debit(
                $advertiserId,
                $budget,
                WalletTransactionReason::AdSpend,
                "Boost campaign for post #{$postId}",
                $advertiserId,
            );

            return $this->campaigns->create([
                'post_id' => $postId,
                'advertiser_id' => $advertiserId,
                'status' => AdCampaignStatus::Pending,
                'budget' => $budget,
                'starts_at' => now(),
                'ends_at' => now()->addDays($days),
            ]);
        });
    }

    public function approve(int $campaignId, int $reviewerId): AdCampaign
    {
        $campaign = $this->findPendingOrFail($campaignId);

        return $this->campaigns->updateStatus($campaign, [
            'status' => AdCampaignStatus::Approved,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(int $campaignId, int $reviewerId, string $reason): AdCampaign
    {
        $campaign = $this->findPendingOrFail($campaignId);

        return DB::transaction(function () use ($campaign, $reviewerId, $reason): AdCampaign {
            $this->wallet->credit(
                (int) $campaign->advertiser_id,
                (int) $campaign->budget,
                WalletTransactionReason::AdRefund,
                "Refund for rejected boost campaign #{$campaign->id}",
                $reviewerId,
            );

            return $this->campaigns->updateStatus($campaign, [
                'status' => AdCampaignStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function cancel(int $campaignId, int $requesterId): AdCampaign
    {
        $campaign = $this->campaigns->findById($campaignId);

        if ($campaign === null || $campaign->status !== AdCampaignStatus::Pending) {
            throw ValidationException::withMessages([
                'campaign' => 'Only pending campaigns can be cancelled.',
            ]);
        }

        if ($campaign->advertiser_id !== $requesterId) {
            throw new AuthorizationException('You can only cancel your own campaigns.');
        }

        return DB::transaction(function () use ($campaign): AdCampaign {
            $this->wallet->credit(
                (int) $campaign->advertiser_id,
                (int) $campaign->budget,
                WalletTransactionReason::AdRefund,
                "Refund for cancelled boost campaign #{$campaign->id}",
                (int) $campaign->advertiser_id,
            );

            return $this->campaigns->updateStatus($campaign, [
                'status' => AdCampaignStatus::Cancelled,
            ]);
        });
    }

    public function myCampaigns(int $advertiserId): Collection
    {
        return $this->campaigns->forAdvertiser($advertiserId);
    }

    public function pendingQueue(int $page): LengthAwarePaginator
    {
        return $this->campaigns->pendingPaginated($page, self::PER_PAGE);
    }

    /**
     * @return Collection<int, Post>
     */
    public function activeForFeed(int $limit = 2): Collection
    {
        return $this->campaigns->activeForFeed($limit)
            ->map(function (AdCampaign $campaign): Post {
                $post = $campaign->post;
                $post->is_sponsored = true;

                return $post;
            });
    }

    /**
     * @return Collection<int, Post>
     */
    public function eligiblePosts(int $advertiserId): Collection
    {
        return Post::query()
            ->where('author_id', $advertiserId)
            ->where('visibility', PostVisibility::Public)
            ->whereNull('group_id')
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, AdCampaign>
     */
    public function expireDueCampaigns(): Collection
    {
        $expired = $this->campaigns->expiredApproved();

        foreach ($expired as $campaign) {
            $this->campaigns->updateStatus($campaign, ['status' => AdCampaignStatus::Completed]);
        }

        return $expired;
    }

    private function findPendingOrFail(int $campaignId): AdCampaign
    {
        $campaign = $this->campaigns->findById($campaignId);

        if ($campaign === null || $campaign->status !== AdCampaignStatus::Pending) {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign is not pending review.',
            ]);
        }

        return $campaign;
    }
}
