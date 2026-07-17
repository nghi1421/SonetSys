<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Auth;

use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_can_list_users(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        User::factory()->count(2)->create();

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_change_a_users_role_and_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        Role::factory()->moderator()->create();
        $target = User::factory()->create();

        $this->putJson("/api/v1/users/{$target->id}", [
            'role' => 'moderator',
            'status' => 'suspended',
        ])
            ->assertOk()
            ->assertJsonPath('data.role.slug', 'moderator')
            ->assertJsonPath('data.status', 'suspended');

        $this->assertSame('moderator', $target->fresh()->role->slug);
        $this->assertSame('suspended', $target->fresh()->status->value);
    }

    public function test_admin_cannot_change_their_own_role_or_status(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/users/{$admin->id}", [
            'role' => 'user',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_non_manager_cannot_change_a_users_role_or_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $target = User::factory()->create();

        $this->putJson("/api/v1/users/{$target->id}", [
            'role' => 'admin',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_updating_a_user_rejects_an_invalid_role(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $target = User::factory()->create();

        $this->putJson("/api/v1/users/{$target->id}", [
            'role' => 'super-admin',
            'status' => 'active',
        ])->assertStatus(422);
    }
}
