<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Modules\Menu\Application\Services\MenuService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);

        $adminRole = Role::query()->where('slug', RoleSlug::Admin->value)->firstOrFail();

        if (! User::query()->where('email', 'admin@sonetsys.test')->exists()) {
            // Deliberately not User::factory() — the factory's definition()
            // calls fake() unconditionally (even for overridden fields), and
            // fakerphp/faker is a require-dev package unavailable in the
            // --no-dev production image this seeder runs in on deploy.
            User::query()->create([
                'role_id' => $adminRole->id,
                'name' => 'Admin',
                'email' => 'admin@sonetsys.test',
                'password' => 'password',
                'status' => UserStatus::Active,
            ]);
        }

        app(MenuService::class)->seedDefaults();
    }
}
