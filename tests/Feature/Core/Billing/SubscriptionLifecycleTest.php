<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Billing;

use App\Core\Billing\Domain\Models\Plan;
use App\Core\Billing\Domain\Models\TenantSubscription;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_trialing_subscription_is_expired_and_tenant_suspended(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'trial']);
        $plan = Plan::query()->create($this->planAttributes());

        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'starts_at' => now()->subDays(20),
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('billing:expire-trials')->assertExitCode(0);

        $this->assertSame('expired', $subscription->fresh()->status->value);
        $this->assertSame('suspended', $tenant->fresh()->status->value);
    }

    public function test_trialing_subscription_not_yet_expired_is_untouched(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'trial']);
        $plan = Plan::query()->create($this->planAttributes());

        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'starts_at' => now(),
            'expires_at' => now()->addDays(10),
        ]);

        $this->artisan('billing:expire-trials')->assertExitCode(0);

        $this->assertSame('trialing', $subscription->fresh()->status->value);
        $this->assertSame('trial', $tenant->fresh()->status->value);
    }

    public function test_active_subscription_is_never_touched_regardless_of_expiry(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::query()->create($this->planAttributes());

        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(365),
            'expires_at' => null,
        ]);

        $this->artisan('billing:expire-trials')->assertExitCode(0);

        $this->assertSame('active', $subscription->fresh()->status->value);
        $this->assertSame('active', $tenant->fresh()->status->value);
    }

    private function planAttributes(): array
    {
        return [
            'name' => 'Pro',
            'slug' => 'pro-'.uniqid(),
            'price_cents' => 2900,
            'interval' => 'month',
            'max_users' => null,
            'features' => [],
            'is_active' => true,
        ];
    }
}
