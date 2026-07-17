<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Core\Auth\Domain\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_can_view_aggregate_stats(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        User::factory()->count(2)->create();

        $this->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonPath('data.users', 3)
            ->assertJsonPath('data.posts', 0)
            ->assertJsonPath('data.groups', 0)
            ->assertJsonPath('data.media', 0)
            ->assertJsonPath('data.storage_bytes', 0);
    }

    public function test_moderator_cannot_view_stats(): void
    {
        Sanctum::actingAs(User::factory()->moderator()->create());

        $this->getJson('/api/v1/admin/stats')->assertForbidden();
    }

    public function test_plain_user_cannot_view_stats(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/stats')->assertForbidden();
    }
}
