<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Tenancy\Domain\Enums\TenantStatus;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => TenantStatus::Active,
            'enabled_modules' => [],
            'settings' => [],
        ];
    }
}
