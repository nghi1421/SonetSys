<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Application\Services;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Auth\Domain\Models\Permission;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Application\DTOs\CreateTenantData;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use App\Core\Tenancy\Domain\Events\TenantCreated;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{tenant: Tenant, admin: User}
     */
    public function create(CreateTenantData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $tenant = $this->tenants->create([
                'name' => $data->name,
                'slug' => $data->slug,
                'status' => TenantStatus::Active,
                'enabled_modules' => $data->enabledModules,
                'settings' => $data->settings,
            ]);

            Role::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => RoleSlug::Member->value],
                ['name' => 'Member'],
            );

            $adminRole = Role::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => RoleSlug::TenantAdmin->value],
                ['name' => 'Tenant Admin'],
            );

            $adminRole->permissions()->sync(
                Permission::query()
                    ->whereIn('slug', array_map(
                        fn (PermissionSlug $p) => $p->value,
                        PermissionSlug::tenantAdminDefaults(),
                    ))
                    ->pluck('id'),
            );

            $admin = $this->users->create([
                'tenant_id' => $tenant->id,
                'role_id' => $adminRole->id,
                'name' => $data->adminName,
                'email' => $data->adminEmail,
                'password' => Hash::make($data->adminPassword),
                'status' => UserStatus::Active,
            ]);

            TenantCreated::dispatch($tenant->id);

            return ['tenant' => $tenant, 'admin' => $admin];
        });
    }
}
