<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Application\Services\ReactionTypeService;
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
            User::query()->create([
                'role_id' => $adminRole->id,
                'name' => 'Admin',
                'email' => 'admin@sonetsys.test',
                'password' => 'password',
                'status' => UserStatus::Active,
            ]);
        }

        app(MenuService::class)->seedDefaults();
        app(ReactionTypeService::class)->seedDefaults();

        $this->call(DemoDataSeeder::class);
    }
}
