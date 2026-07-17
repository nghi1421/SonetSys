<?php

declare(strict_types=1);

namespace App\Modules\Story;

use App\Modules\Story\Application\Contracts\StoryRepositoryInterface;
use App\Modules\Story\Domain\Models\Story;
use App\Modules\Story\Infrastructure\Repositories\EloquentStoryRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class StoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StoryRepositoryInterface::class, EloquentStoryRepository::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            'story' => Story::class,
        ]);
    }
}
