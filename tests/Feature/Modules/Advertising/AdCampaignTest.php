<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Advertising;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Advertising\Domain\Models\AdCampaign;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function credit(User $user, int $amount): void
    {
        $this->app->make(WalletService::class)->credit($user->id, $amount, WalletTransactionReason::AdminTopup);
    }

    private function createPublicPost(): int
    {
        return $this->postJson('/api/v1/posts', [
            'body' => 'Boost me',
            'visibility' => 'public',
        ])->assertCreated()->json('data.id');
    }

    public function test_submitting_a_valid_boost_debits_the_wallet_and_creates_a_pending_campaign(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);

        $postId = $this->createPublicPost();

        $response = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.budget', 300);

        $this->assertDatabaseHas('ad_campaigns', [
            'post_id' => $postId,
            'advertiser_id' => $advertiser->id,
            'status' => 'pending',
            'budget' => 300,
        ]);

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 700);
    }

    public function test_submitting_a_group_post_is_rejected_and_nothing_is_created_or_debited(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);

        $groupId = $this->postJson('/api/v1/groups', ['name' => 'Ad Group'])
            ->assertCreated()
            ->json('data.id');

        $postId = $this->postJson("/api/v1/groups/{$groupId}/posts", ['body' => 'Group post'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('ad_campaigns', ['post_id' => $postId]);
        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 1000);
    }

    public function test_submitting_a_members_visibility_post_is_rejected(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Members only', 'visibility' => 'members'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('ad_campaigns', ['post_id' => $postId]);
    }

    public function test_submitting_a_post_not_owned_by_the_requester_is_rejected(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs($author);
        $postId = $this->createPublicPost();

        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);

        $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('ad_campaigns', ['post_id' => $postId]);
    }

    public function test_submitting_with_insufficient_wallet_balance_is_rejected_and_no_campaign_row_exists(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);

        $postId = $this->createPublicPost();

        $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 500,
            'days' => 7,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('ad_campaigns', ['post_id' => $postId]);
        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 0);
    }

    public function test_approving_as_admin_flips_status_to_approved_and_funds_remain_spent(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);
        $postId = $this->createPublicPost();

        $campaignId = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertCreated()->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/ads/{$campaignId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('ad_campaigns', ['id' => $campaignId, 'status' => 'approved']);

        Sanctum::actingAs($advertiser);
        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 700);
    }

    public function test_rejecting_as_admin_refunds_the_full_budget_and_flips_to_rejected(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);
        $postId = $this->createPublicPost();

        $campaignId = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertCreated()->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/ads/{$campaignId}/reject", ['reason' => 'Not appropriate'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Not appropriate');

        Sanctum::actingAs($advertiser);
        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 1000);
    }

    public function test_a_non_admin_gets_403_approving_rejecting_and_listing_the_admin_queue(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);
        $postId = $this->createPublicPost();

        $campaignId = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertCreated()->json('data.id');

        $nonAdmin = User::factory()->create();
        Sanctum::actingAs($nonAdmin);

        $this->getJson('/api/v1/admin/ads')->assertForbidden();
        $this->postJson("/api/v1/admin/ads/{$campaignId}/approve")->assertForbidden();
        $this->postJson("/api/v1/admin/ads/{$campaignId}/reject", ['reason' => 'nope'])->assertForbidden();
    }

    public function test_cancelling_while_pending_as_owning_advertiser_refunds_and_flips_to_cancelled(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);
        $postId = $this->createPublicPost();

        $campaignId = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertCreated()->json('data.id');

        $this->deleteJson("/api/v1/ads/campaigns/{$campaignId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 1000);
    }

    public function test_cancelling_by_a_different_user_is_rejected(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);
        $postId = $this->createPublicPost();

        $campaignId = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertCreated()->json('data.id');

        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $this->deleteJson("/api/v1/ads/campaigns/{$campaignId}")->assertForbidden();

        $this->assertDatabaseHas('ad_campaigns', ['id' => $campaignId, 'status' => 'pending']);
    }

    public function test_feed_first_page_includes_sponsored_post_and_a_subsequent_page_does_not(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);
        $sponsoredPostId = $this->createPublicPost();

        $campaignId = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $sponsoredPostId,
            'budget' => 300,
            'days' => 7,
        ])->assertCreated()->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/admin/ads/{$campaignId}/approve")->assertOk();

        $organicAuthor = User::factory()->create();
        Sanctum::actingAs($organicAuthor);
        $olderOrganicId = $this->postJson('/api/v1/posts', ['body' => 'Older organic', 'visibility' => 'public'])
            ->assertCreated()->json('data.id');
        $newerOrganicId = $this->postJson('/api/v1/posts', ['body' => 'Newer organic', 'visibility' => 'public'])
            ->assertCreated()->json('data.id');

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $firstPage = $this->getJson('/api/v1/posts?limit=1')->assertOk();
        $firstPageIds = collect($firstPage->json('data'))->pluck('id');

        $this->assertTrue($firstPageIds->contains($sponsoredPostId));
        $sponsoredEntry = collect($firstPage->json('data'))->firstWhere('id', $sponsoredPostId);
        $this->assertTrue($sponsoredEntry['is_sponsored']);
        $this->assertTrue($firstPageIds->contains($newerOrganicId));

        $nextCursor = $firstPage->json('meta.next_cursor');
        $this->assertNotNull($nextCursor);

        $secondPage = $this->getJson('/api/v1/posts?limit=1&cursor='.urlencode($nextCursor))->assertOk();
        $secondPageIds = collect($secondPage->json('data'))->pluck('id');

        $this->assertFalse($secondPageIds->contains($sponsoredPostId));
        $this->assertTrue($secondPageIds->contains($olderOrganicId));
    }

    public function test_ads_expire_command_sweeps_a_past_ends_at_approved_campaign_to_completed(): void
    {
        $advertiser = User::factory()->create();
        Sanctum::actingAs($advertiser);
        $this->credit($advertiser, 1000);
        $postId = $this->createPublicPost();

        $campaignId = $this->postJson('/api/v1/ads/campaigns', [
            'post_id' => $postId,
            'budget' => 300,
            'days' => 7,
        ])->assertCreated()->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/admin/ads/{$campaignId}/approve")->assertOk();

        AdCampaign::query()->where('id', $campaignId)->update(['ends_at' => now()->subDay()]);

        Artisan::call('ads:expire');

        $this->assertDatabaseHas('ad_campaigns', ['id' => $campaignId, 'status' => 'completed']);
    }
}
