<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Billing;

use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Core\Billing\Domain\Models\Plan;
use App\Core\Billing\Domain\Models\TenantSubscription;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SubscriptionUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_switching_to_free_plan_reactivates_a_suspended_tenant_immediately(): void
    {
        $role = Role::factory()->tenantAdmin()->create();
        $tenant = $role->tenant;
        $tenant->fill(['status' => 'suspended'])->save();

        $paidPlan = Plan::query()->create($this->planAttributes(['slug' => 'pro', 'price_cents' => 2900]));
        $freePlan = Plan::query()->create($this->planAttributes(['slug' => 'free', 'price_cents' => 0]));

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $paidPlan->id,
            'status' => 'expired',
            'starts_at' => now()->subDays(20),
            'expires_at' => now()->subDay(),
        ]);

        Sanctum::actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role_id' => $role->id]));

        $this->putJson('/api/v1/subscription', ['plan_id' => $freePlan->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.expires_at', null);

        $this->assertSame('active', $tenant->fresh()->status->value);
    }

    public function test_switching_to_a_paid_plan_starts_a_new_trial(): void
    {
        $role = Role::factory()->tenantAdmin()->create();
        $tenant = $role->tenant;

        $freePlan = Plan::query()->create($this->planAttributes(['slug' => 'free', 'price_cents' => 0]));
        $paidPlan = Plan::query()->create($this->planAttributes(['slug' => 'pro', 'price_cents' => 2900]));

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => null,
        ]);

        Sanctum::actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role_id' => $role->id]));

        $this->putJson('/api/v1/subscription', ['plan_id' => $paidPlan->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'trialing');

        $this->assertSame('trial', $tenant->fresh()->status->value);
    }

    public function test_a_suspended_tenant_cannot_escape_suspension_by_picking_a_paid_plan(): void
    {
        $role = Role::factory()->tenantAdmin()->create();
        $tenant = $role->tenant;
        $tenant->fill(['status' => 'suspended'])->save();

        $paidPlan = Plan::query()->create($this->planAttributes(['slug' => 'pro', 'price_cents' => 2900]));
        $anotherPaidPlan = Plan::query()->create($this->planAttributes(['slug' => 'elite', 'price_cents' => 4900]));

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $paidPlan->id,
            'status' => 'expired',
            'starts_at' => now()->subDays(20),
            'expires_at' => now()->subDay(),
        ]);

        Sanctum::actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role_id' => $role->id]));

        $this->putJson('/api/v1/subscription', ['plan_id' => $anotherPaidPlan->id])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.plan_id.0', fn ($message) => is_string($message));

        $this->assertSame('suspended', $tenant->fresh()->status->value);
    }

    public function test_regular_member_cannot_change_the_tenant_plan(): void
    {
        $tenantAdminRole = Role::factory()->tenantAdmin()->create();
        $tenant = $tenantAdminRole->tenant;

        $memberRole = Role::factory()->create(['tenant_id' => $tenant->id]);
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role_id' => $memberRole->id]);

        $plan = Plan::query()->create($this->planAttributes());

        Sanctum::actingAs($member);

        $this->putJson('/api/v1/subscription', ['plan_id' => $plan->id])->assertForbidden();
    }

    private function planAttributes(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Plan',
            'slug' => 'plan-'.uniqid(),
            'price_cents' => 0,
            'interval' => 'free',
            'max_users' => null,
            'features' => [],
            'is_active' => true,
        ], $overrides);
    }
}
