<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Models\Permission;
use App\Core\Auth\Domain\Models\Role;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::query()->firstOrCreate(
            ['slug' => RoleSlug::Admin->value],
            ['name' => 'Admin', 'is_system' => true],
        );
        $admin->permissions()->sync(
            Permission::query()
                ->whereIn('slug', array_map(fn (PermissionSlug $p) => $p->value, PermissionSlug::adminDefaults()))
                ->pluck('id'),
        );

        $moderator = Role::query()->firstOrCreate(
            ['slug' => RoleSlug::Moderator->value],
            ['name' => 'Moderator', 'is_system' => true],
        );
        $moderator->permissions()->sync(
            Permission::query()
                ->whereIn('slug', array_map(fn (PermissionSlug $p) => $p->value, PermissionSlug::moderatorDefaults()))
                ->pluck('id'),
        );

        Role::query()->firstOrCreate(
            ['slug' => RoleSlug::User->value],
            ['name' => 'User', 'is_system' => true],
        );
    }
}
