<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Support\Collection;

final class DemoUserStep
{
    private const REGULAR_COUNT = 42;

    private const MODERATOR_COUNT = 2;

    /**
     * @return Collection<int, User>
     */
    public function run(): Collection
    {
        $moderators = User::factory()->count(self::MODERATOR_COUNT)->moderator()->create();
        $regulars = User::factory()->count(self::REGULAR_COUNT)->create();

        return $moderators->merge($regulars)->values();
    }
}
