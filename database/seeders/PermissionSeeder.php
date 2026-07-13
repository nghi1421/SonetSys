<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Models\Permission;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionSlug::cases() as $slug) {
            Permission::query()->firstOrCreate(
                ['slug' => $slug->value],
                ['group' => $slug->group()],
            );
        }
    }
}
