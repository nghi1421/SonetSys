<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Overriding 'tenant_id' via create()/state() alone will desync it from
 * role_id's tenant (definition() creates its own tenant+role internally).
 * Use forTenant()/tenantAdmin()/superAdmin() to keep both in sync.
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'role_id' => Role::factory()->recycle($tenant)->create()->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'role_id' => Role::factory()->recycle($tenant)->create()->id,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'role_id' => Role::factory()->superAdmin()->create()->id,
        ]);
    }

    /**
     * Pass an existing $tenant to attach the admin to it — overriding
     * 'tenant_id' via create() instead would desync it from role_id's tenant.
     */
    public function tenantAdmin(?Tenant $tenant = null): static
    {
        return $this->state(function (array $attributes) use ($tenant) {
            $tenant ??= Tenant::factory()->create();

            return [
                'tenant_id' => $tenant->id,
                'role_id' => Role::factory()->tenantAdmin()->recycle($tenant)->create()->id,
            ];
        });
    }
}
