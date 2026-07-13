<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Domain\Enums\RoleSlug;
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
}
