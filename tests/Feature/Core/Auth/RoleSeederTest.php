<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Auth;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_role_has_every_admin_default_permission(): void
    {
        $admin = Role::query()->where('slug', RoleSlug::Admin->value)->firstOrFail();

        $slugs = $admin->permissions()->pluck('slug')->sort()->values()->all();
        $expected = array_map(fn (PermissionSlug $p) => $p->value, PermissionSlug::adminDefaults());
        sort($expected);

        $this->assertSame($expected, $slugs);
    }

    public function test_moderator_role_has_exactly_the_moderator_default_permissions(): void
    {
        $moderator = Role::query()->where('slug', RoleSlug::Moderator->value)->firstOrFail();

        $slugs = $moderator->permissions()->pluck('slug')->sort()->values()->all();
        $expected = array_map(fn (PermissionSlug $p) => $p->value, PermissionSlug::moderatorDefaults());
        sort($expected);

        $this->assertSame($expected, $slugs);
    }

    public function test_user_role_has_no_permissions(): void
    {
        $user = Role::query()->where('slug', RoleSlug::User->value)->firstOrFail();

        $this->assertSame(0, $user->permissions()->count());
    }
}
