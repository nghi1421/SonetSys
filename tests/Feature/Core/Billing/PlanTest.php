<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Billing;

use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Core\Billing\Domain\Models\Plan;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_anyone_can_list_active_plans_without_authentication(): void
    {
        Plan::query()->create($this->planAttributes(['name' => 'Free', 'slug' => 'free', 'is_active' => true]));
        Plan::query()->create($this->planAttributes(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]));

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'free');
    }

    public function test_non_super_admin_cannot_manage_plans(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/plans')->assertForbidden();
        $this->postJson('/api/v1/admin/plans', $this->planPayload())->assertForbidden();
    }

    public function test_tenant_admin_without_plans_manage_cannot_manage_plans(): void
    {
        $role = Role::factory()->tenantAdmin()->create();
        Sanctum::actingAs(User::factory()->create(['tenant_id' => $role->tenant_id, 'role_id' => $role->id]));

        $this->postJson('/api/v1/admin/plans', $this->planPayload())->assertForbidden();
    }

    public function test_super_admin_can_create_update_and_delete_plans(): void
    {
        $role = Role::factory()->superAdmin()->create();
        Sanctum::actingAs(User::factory()->create(['tenant_id' => null, 'role_id' => $role->id]));

        $planId = $this->postJson('/api/v1/admin/plans', $this->planPayload())
            ->assertCreated()
            ->json('data.id');

        $this->getJson('/api/v1/admin/plans')->assertOk()->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/admin/plans/{$planId}", [
            'name' => 'Pro Updated',
            'price_cents' => 3900,
            'interval' => 'month',
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.name', 'Pro Updated');

        // Now hidden from public listing since it was deactivated.
        $this->getJson('/api/v1/plans')->assertJsonCount(0, 'data');

        $this->deleteJson("/api/v1/admin/plans/{$planId}")->assertOk();
        $this->assertDatabaseMissing('plans', ['id' => $planId]);
    }

    public function test_a_plan_with_active_subscriptions_cannot_be_deleted(): void
    {
        $superAdminRole = Role::factory()->superAdmin()->create();
        Sanctum::actingAs(User::factory()->create(['tenant_id' => null, 'role_id' => $superAdminRole->id]));

        $plan = Plan::query()->create($this->planAttributes(['slug' => 'free', 'price_cents' => 0]));

        $this->postJson('/api/v1/tenant-registrations', [
            'company_name' => 'Acme Inc',
            'company_slug' => 'acme-inc',
            'admin_name' => 'Jane Admin',
            'admin_email' => 'jane@acme-inc.test',
            'admin_password' => 'password123',
            'plan_id' => $plan->id,
        ])->assertCreated();

        $this->deleteJson("/api/v1/admin/plans/{$plan->id}")->assertStatus(422);
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    private function planPayload(): array
    {
        return [
            'name' => 'Pro',
            'slug' => 'pro',
            'price_cents' => 2900,
            'interval' => 'month',
            'max_users' => 50,
            'features' => ['custom_domain'],
        ];
    }

    private function planAttributes(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Plan',
            'slug' => 'plan',
            'price_cents' => 0,
            'interval' => 'free',
            'max_users' => null,
            'features' => [],
            'is_active' => true,
        ], $overrides);
    }
}
