<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'super-admin@sonetsys.test',
        ]);
    }
}
