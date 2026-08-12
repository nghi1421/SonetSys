<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Subscription;

use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Modules\Subscription\Domain\Models\UserSubscription;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SubscriptionTest extends TestCase
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

    private function ensureUserRoleExists(): void
    {
        Role::query()->firstOrCreate(
            ['slug' => RoleSlug::User->value],
            ['name' => 'User', 'is_system' => true],
        );
    }

    public function test_subscribing_to_a_plan_debits_the_wallet_and_creates_an_active_subscription(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 2000);

        $response = $this->postJson('/api/v1/subscriptions', ['plan' => 'basic']);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'active');
        $response->assertJsonPath('data.auto_renew', true);
        $response->assertJsonPath('data.plan', 'basic');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'plan' => 'basic',
            'status' => 'active',
            'price' => 1000,
        ]);

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 1000);
    }

    public function test_subscribing_with_insufficient_wallet_balance_is_rejected_and_no_row_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])->assertStatus(422);

        $this->assertDatabaseMissing('user_subscriptions', ['user_id' => $user->id]);
        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 0);
    }

    public function test_subscribing_while_already_active_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 5000);

        $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])->assertCreated();

        $this->postJson('/api/v1/subscriptions', ['plan' => 'pro'])
            ->assertStatus(422)
            ->assertJsonPath('meta.errors.plan.0', fn ($m) => is_string($m));

        $this->assertDatabaseCount('user_subscriptions', 1);
        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 4000);
    }

    public function test_validation_rejects_an_unknown_plan_value(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/subscriptions', ['plan' => 'ultra'])->assertStatus(422);
    }

    public function test_cancelling_sets_auto_renew_false_but_keeps_status_active_and_access(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 2000);

        $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])->assertCreated();

        $response = $this->deleteJson('/api/v1/subscriptions/current');
        $response->assertOk();
        $response->assertJsonPath('data.status', 'active');
        $response->assertJsonPath('data.auto_renew', false);
        $this->assertNotNull($response->json('data.cancelled_at'));

        $this->getJson('/api/v1/subscriptions/current')->assertJsonPath('data.status', 'active');
        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.is_premium', true);
    }

    public function test_cancelling_twice_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 2000);

        $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])->assertCreated();
        $this->deleteJson('/api/v1/subscriptions/current')->assertOk();
        $this->deleteJson('/api/v1/subscriptions/current')->assertStatus(422);
    }

    public function test_cancelling_with_no_active_subscription_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/subscriptions/current')->assertStatus(422);
    }

    public function test_renew_command_extends_the_period_on_successful_debit_for_auto_renewing_subscriptions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 3000);

        $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])
            ->assertCreated()
            ->json('data.id');

        $forcedPastEnd = now()->subDay();

        UserSubscription::query()->whereKey($subscriptionId)->update([
            'current_period_end' => $forcedPastEnd,
        ]);

        Artisan::call('subscriptions:renew');

        $subscription = UserSubscription::query()->findOrFail($subscriptionId);
        $this->assertSame('active', $subscription->status->value);
        $this->assertTrue($subscription->current_period_end->gt($forcedPastEnd));
        $this->assertTrue($subscription->current_period_end->isFuture());

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 1000);
    }

    public function test_renew_command_expires_a_subscription_on_failed_debit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 1000);

        $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])
            ->assertCreated()
            ->json('data.id');

        UserSubscription::query()->whereKey($subscriptionId)->update([
            'current_period_end' => now()->subDay(),
        ]);

        Artisan::call('subscriptions:renew');

        $subscription = UserSubscription::query()->findOrFail($subscriptionId);
        $this->assertSame('expired', $subscription->status->value);
        $this->assertFalse($subscription->auto_renew);
        $this->assertNotNull($subscription->ended_at);
        $this->assertNotNull($subscription->last_payment_failed_at);
    }

    public function test_renew_command_expires_a_cancelled_subscription_whose_period_has_ended_without_debiting_again(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 2000);

        $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])
            ->assertCreated()
            ->json('data.id');

        $this->deleteJson('/api/v1/subscriptions/current')->assertOk();

        UserSubscription::query()->whereKey($subscriptionId)->update([
            'current_period_end' => now()->subDay(),
        ]);

        Artisan::call('subscriptions:renew');

        $subscription = UserSubscription::query()->findOrFail($subscriptionId);
        $this->assertSame('expired', $subscription->status->value);

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 1000);
    }

    public function test_the_premium_badge_appears_on_the_users_own_resource_and_public_profile_only_while_subscribed(): void
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();

        Sanctum::actingAs($user);
        $this->credit($user, 1000);

        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.is_premium', false);

        $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])
            ->assertCreated()
            ->json('data.id');

        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.is_premium', true);

        Sanctum::actingAs($viewer);
        $this->getJson("/api/v1/users/{$user->id}/profile")->assertJsonPath('data.is_premium', true);

        UserSubscription::query()->whereKey($subscriptionId)->update([
            'current_period_end' => now()->subDay(),
        ]);
        Artisan::call('subscriptions:renew');

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.is_premium', false);

        Sanctum::actingAs($viewer);
        $this->getJson("/api/v1/users/{$user->id}/profile")->assertJsonPath('data.is_premium', false);
    }

    public function test_admin_can_list_all_subscriptions(): void
    {
        $userA = User::factory()->create();
        Sanctum::actingAs($userA);
        $this->credit($userA, 2000);
        $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])->assertCreated();

        $userB = User::factory()->create();
        Sanctum::actingAs($userB);
        $this->credit($userB, 3000);
        $this->postJson('/api/v1/subscriptions', ['plan' => 'pro'])->assertCreated();

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/subscriptions');
        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertNotNull($response->json('data.0.user.id'));
    }

    public function test_admin_can_force_cancel_a_users_subscription(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 2000);
        $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])
            ->assertCreated()
            ->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/subscriptions/{$subscriptionId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.auto_renew', false);

        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscriptionId,
            'cancelled_by' => $admin->id,
        ]);
    }

    public function test_a_non_admin_gets_403_on_admin_subscription_endpoints(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->credit($user, 2000);
        $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])
            ->assertCreated()
            ->json('data.id');

        $this->getJson('/api/v1/admin/subscriptions')->assertForbidden();
        $this->postJson("/api/v1/admin/subscriptions/{$subscriptionId}/cancel")->assertForbidden();
    }

    public function test_boost_fee_hook_costs_subscribers_and_non_subscribers_identically_while_disabled(): void
    {
        config(['advertising.platform_fee_percent' => 0]);

        $subscriber = User::factory()->create();
        Sanctum::actingAs($subscriber);
        $this->credit($subscriber, 3000);
        $this->postJson('/api/v1/subscriptions', ['plan' => 'pro'])->assertCreated();

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Boost me', 'visibility' => 'public'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/v1/ads/campaigns', ['post_id' => $postId, 'budget' => 300, 'days' => 7])
            ->assertCreated();

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 200);
    }

    public function test_boost_fee_hook_waives_the_fee_per_plan_when_enabled(): void
    {
        config(['advertising.platform_fee_percent' => 10]);

        $subscriber = User::factory()->create();
        Sanctum::actingAs($subscriber);
        $this->credit($subscriber, 3000);
        $this->postJson('/api/v1/subscriptions', ['plan' => 'pro'])->assertCreated();

        $postId = $this->postJson('/api/v1/posts', ['body' => 'Boost me', 'visibility' => 'public'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/v1/ads/campaigns', ['post_id' => $postId, 'budget' => 300, 'days' => 7])
            ->assertCreated();

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 200);

        $nonSubscriber = User::factory()->create();
        Sanctum::actingAs($nonSubscriber);
        $this->credit($nonSubscriber, 3000);

        $postId2 = $this->postJson('/api/v1/posts', ['body' => 'Boost me too', 'visibility' => 'public'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/v1/ads/campaigns', ['post_id' => $postId2, 'budget' => 300, 'days' => 7])
            ->assertCreated();

        $this->getJson('/api/v1/wallet')->assertJsonPath('data.balance', 2670);
    }

    public function test_registering_a_new_user_automatically_creates_an_active_free_subscription(): void
    {
        $this->ensureUserRoleExists();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated();
        $userId = $response->json('data.user.id');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $userId,
            'plan' => 'free',
            'status' => 'active',
            'price' => 0,
        ]);

        $this->assertFalse($response->json('data.user.is_premium'));
    }

    public function test_the_free_plan_does_not_count_as_premium_but_current_subscription_reports_it(): void
    {
        $this->ensureUserRoleExists();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Free User',
            'email' => 'free-user@example.com',
            'password' => 'password123',
        ]);
        $user = User::query()->findOrFail($response->json('data.user.id'));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/subscriptions/current')
            ->assertJsonPath('data.plan', 'free')
            ->assertJsonPath('data.status', 'active');

        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.is_premium', false);
    }

    public function test_subscribing_to_a_paid_plan_while_on_free_replaces_it_and_becomes_premium(): void
    {
        $this->ensureUserRoleExists();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Upgrading User',
            'email' => 'upgrading-user@example.com',
            'password' => 'password123',
        ]);
        $userId = $response->json('data.user.id');

        $user = User::query()->findOrFail($userId);
        $this->credit($user, 1000);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/subscriptions', ['plan' => 'basic'])
            ->assertCreated()
            ->assertJsonPath('data.plan', 'basic')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $userId,
            'plan' => 'free',
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $userId,
            'plan' => 'basic',
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('user_subscriptions', 2);

        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.is_premium', true);
    }
}
