<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EnsureTenantActiveMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_suspended_tenants_user_can_still_reach_auth_and_subscription_endpoints(): void
    {
        $role = Role::factory()->tenantAdmin()->create();
        $role->tenant->fill(['status' => 'suspended'])->save();

        Sanctum::actingAs(User::factory()->create(['tenant_id' => $role->tenant_id, 'role_id' => $role->id]));

        $this->getJson('/api/v1/auth/me')->assertOk();
        $this->getJson('/api/v1/subscription')->assertOk();
    }

    public function test_a_suspended_tenants_user_is_blocked_from_product_feature_routes(): void
    {
        $role = Role::factory()->tenantAdmin()->create();
        $role->tenant->fill(['status' => 'suspended'])->save();

        Sanctum::actingAs(User::factory()->create(['tenant_id' => $role->tenant_id, 'role_id' => $role->id]));

        $this->getJson('/api/v1/posts')->assertForbidden();
        $this->getJson('/api/v1/groups')->assertForbidden();
        $this->getJson('/api/v1/menu')->assertForbidden();
    }

    public function test_an_active_tenants_user_is_unaffected(): void
    {
        $role = Role::factory()->tenantAdmin()->create();

        Sanctum::actingAs(User::factory()->create(['tenant_id' => $role->tenant_id, 'role_id' => $role->id]));

        $this->getJson('/api/v1/posts')->assertOk();
        $this->getJson('/api/v1/groups')->assertOk();
        $this->getJson('/api/v1/menu')->assertOk();
    }
}
