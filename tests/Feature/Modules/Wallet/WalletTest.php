<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Wallet;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use App\Modules\Wallet\Domain\Models\Wallet;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_crediting_a_user_with_no_wallet_yet_lazily_creates_one(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(WalletService::class);

        $this->assertDatabaseMissing('wallets', ['user_id' => $user->id]);

        $transaction = $service->credit($user->id, 500, WalletTransactionReason::AdminTopup);

        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 500]);
        $this->assertSame(500, $transaction->balance_after);
        $this->assertSame(500, $transaction->amount);
    }

    public function test_crediting_twice_accumulates_correctly(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(WalletService::class);

        $service->credit($user->id, 300, WalletTransactionReason::AdminTopup);
        $transaction = $service->credit($user->id, 200, WalletTransactionReason::AdminTopup);

        $this->assertSame(500, $transaction->balance_after);
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 500]);
        $this->assertDatabaseCount('wallet_transactions', 2);
    }

    public function test_debiting_decreases_balance_and_appends_a_row(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(WalletService::class);

        $service->credit($user->id, 500, WalletTransactionReason::AdminTopup);
        $transaction = $service->debit($user->id, 200, WalletTransactionReason::AdSpend);

        $this->assertSame(300, $transaction->balance_after);
        $this->assertSame(200, $transaction->amount);
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 300]);
    }

    public function test_debiting_more_than_the_balance_throws_and_leaves_balance_unchanged(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(WalletService::class);

        $service->credit($user->id, 100, WalletTransactionReason::AdminTopup);

        $this->expectException(ValidationException::class);

        try {
            $service->debit($user->id, 500, WalletTransactionReason::AdSpend);
        } finally {
            $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 100]);
        }
    }

    public function test_get_wallet_returns_the_correct_balance_for_the_authenticated_user_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $service = $this->app->make(WalletService::class);

        $service->credit($user->id, 750, WalletTransactionReason::AdminTopup);
        $service->credit($otherUser->id, 100, WalletTransactionReason::AdminTopup);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 750);
    }

    public function test_get_wallet_transactions_returns_the_authenticated_users_own_ledger_paginated(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $service = $this->app->make(WalletService::class);

        $service->credit($user->id, 100, WalletTransactionReason::AdminTopup);
        $service->credit($user->id, 50, WalletTransactionReason::AdminTopup);
        $service->credit($otherUser->id, 999, WalletTransactionReason::AdminTopup);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/wallet/transactions')->assertOk();

        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('meta.page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_a_non_admin_gets_403_on_admin_topup_and_admin_wallets(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/admin/users/{$target->id}/wallet/topup", ['amount' => 100])
            ->assertForbidden();

        $this->getJson('/api/v1/admin/wallets')->assertForbidden();
    }

    public function test_an_admin_can_top_up_another_users_wallet(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/users/{$target->id}/wallet/topup", [
            'amount' => 400,
            'note' => 'Welcome bonus',
        ])
            ->assertOk()
            ->assertJsonPath('data.balance', 400);

        $this->assertDatabaseHas('wallets', ['user_id' => $target->id, 'balance' => 400]);

        $wallet = Wallet::query()->where('user_id', $target->id)->firstOrFail();
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'reason' => 'admin_topup',
            'amount' => 400,
            'balance_after' => 400,
            'description' => 'Welcome bonus',
            'created_by' => $admin->id,
        ]);
    }
}
