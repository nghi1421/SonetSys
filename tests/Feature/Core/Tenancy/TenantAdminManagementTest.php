<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Tenancy;

use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Core\Tenancy\Domain\Models\Tenant;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class TenantAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_super_admin_can_list_suspend_and_reactivate_tenants(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $tenant = Tenant::factory()->create(['status' => 'active']);

        $this->getJson('/api/v1/admin/tenants')
            ->assertOk()
            ->assertJsonPath('data.0.tenant.id', fn ($id) => is_int($id));

        $this->postJson("/api/v1/admin/tenants/{$tenant->id}/suspend")
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertSame('suspended', $tenant->fresh()->status->value);

        $this->postJson("/api/v1/admin/tenants/{$tenant->id}/reactivate")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertSame('active', $tenant->fresh()->status->value);
    }

    public function test_non_super_admin_cannot_manage_tenants(): void
    {
        $role = Role::factory()->tenantAdmin()->create();
        Sanctum::actingAs(User::factory()->create(['tenant_id' => $role->tenant_id, 'role_id' => $role->id]));

        $tenant = Tenant::factory()->create();

        $this->getJson('/api/v1/admin/tenants')->assertForbidden();
        $this->postJson("/api/v1/admin/tenants/{$tenant->id}/suspend")->assertForbidden();
    }

    public function test_super_admin_can_reach_a_suspended_tenants_management_endpoint(): void
    {
        // Confirms admin/tenants routes are NOT behind tenant.active — a
        // Super Admin has no tenant_id, so this is naturally unaffected,
        // but this test locks in that these routes stay reachable.
        Sanctum::actingAs($this->superAdmin());

        $tenant = Tenant::factory()->create(['status' => 'suspended']);

        $this->getJson('/api/v1/admin/tenants')->assertOk();
        $this->postJson("/api/v1/admin/tenants/{$tenant->id}/reactivate")->assertOk();
    }

    private function superAdmin(): User
    {
        $role = Role::factory()->superAdmin()->create();

        return User::factory()->create(['tenant_id' => null, 'role_id' => $role->id]);
    }
}
