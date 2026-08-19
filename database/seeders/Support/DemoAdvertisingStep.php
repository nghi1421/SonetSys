<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Advertising\Application\Services\AdCampaignService;
use App\Modules\Advertising\Domain\Models\AdCampaign;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Models\Post;

final class DemoAdvertisingStep
{
    private const CAMPAIGN_COUNT = 14;

    public function __construct(
        private readonly AdCampaignService $campaigns,
    ) {}

    public function run(): void
    {
        $reviewerId = User::query()->where('email', 'admin@sonetsys.test')->value('id');

        $eligiblePosts = Post::query()
            ->where('visibility', PostVisibility::Public)
            ->whereNull('group_id')
            ->whereNull('shared_post_id')
            ->inRandomOrder()
            ->limit(self::CAMPAIGN_COUNT)
            ->get();

        foreach ($eligiblePosts as $index => $post) {
            $campaign = $this->campaigns->submit(
                advertiserId: (int) $post->author_id,
                postId: $post->id,
                budget: fake()->numberBetween(200, 1500),
                days: fake()->numberBetween(3, 14),
            );

            $this->reviewCampaign($campaign, $index, $reviewerId);
        }
    }

    private function reviewCampaign(AdCampaign $campaign, int $index, ?int $reviewerId): void
    {
        if ($reviewerId === null) {
            return;
        }

        // Most campaigns get approved, a couple stay pending, one gets rejected —
        // an ads dashboard with only "pending" or only "approved" rows looks fake.
        if ($index % 7 === 6) {
            $this->campaigns->reject($campaign->id, $reviewerId, 'Post does not meet community guidelines for boosted content.');

            return;
        }

        if ($index % 5 === 4) {
            return;
        }

        $this->campaigns->approve($campaign->id, $reviewerId);
    }
}
