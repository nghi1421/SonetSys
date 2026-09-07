<?php

declare(strict_types=1);

namespace App\Modules\Song;

use App\Modules\Song\Application\Contracts\SongRepositoryInterface;
use App\Modules\Song\Domain\Models\Song;
use App\Modules\Song\Infrastructure\Repositories\EloquentSongRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class SongServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SongRepositoryInterface::class, EloquentSongRepository::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            'song' => Song::class,
        ]);
    }
}
