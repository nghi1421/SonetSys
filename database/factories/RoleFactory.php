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
            'tenant_id' => TenantFactory::new(),
            'name' => 'Member',
            'slug' => RoleSlug::Member->value,
            'is_system' => false,
        ];
    }

    public function tenantAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Tenant Admin',
            'slug' => RoleSlug::TenantAdmin->value,
            'is_system' => false,
        ])->afterCreating(function (Role $role): void {
            $role->permissions()->sync(
                Permission::query()->whereIn('slug', [
                    PermissionSlug::UsersView->value,
                    PermissionSlug::UsersManage->value,
                    PermissionSlug::RolesManage->value,
                ])->pluck('id'),
            );
        });
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => null,
            'name' => 'Super Admin',
            'slug' => RoleSlug::SuperAdmin->value,
            'is_system' => true,
        ])->afterCreating(function (Role $role): void {
            $role->permissions()->sync(Permission::query()->pluck('id'));
        });
    }
}
