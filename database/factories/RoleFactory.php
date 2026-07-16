<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Models\Permission;
use App\Core\Auth\Domain\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => 'User',
            'slug' => RoleSlug::User->value,
            'is_system' => false,
        ];
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Moderator',
            'slug' => RoleSlug::Moderator->value,
            'is_system' => true,
        ])->afterCreating(function (Role $role): void {
            $role->permissions()->sync(
                Permission::query()
                    ->whereIn('slug', array_map(fn (PermissionSlug $p) => $p->value, PermissionSlug::moderatorDefaults()))
                    ->pluck('id'),
            );
        });
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Admin',
            'slug' => RoleSlug::Admin->value,
            'is_system' => true,
        ])->afterCreating(function (Role $role): void {
            $role->permissions()->sync(
                Permission::query()
                    ->whereIn('slug', array_map(fn (PermissionSlug $p) => $p->value, PermissionSlug::adminDefaults()))
                    ->pluck('id'),
            );
        });
    }
}
