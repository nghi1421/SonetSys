<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Application\Services;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Models\Permission;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Application\DTOs\CreateTenantData;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Support\Facades\DB;

final class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    public function create(CreateTenantData $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
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

            return $tenant;
        });
    }
}
