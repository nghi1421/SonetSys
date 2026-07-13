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
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Platform-level SuperAdmin: no tenant, system role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'role_id' => Role::factory()->create([
                'tenant_id' => null,
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'is_system' => true,
            ])->id,
        ]);
    }
}
