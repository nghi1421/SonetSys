<?php

declare(strict_types=1);

namespace App\Modules\Song\Infrastructure\Repositories;

use App\Modules\Song\Application\Contracts\SongRepositoryInterface;
use App\Modules\Song\Domain\Models\Song;
use Illuminate\Support\Collection;

final class EloquentSongRepository implements SongRepositoryInterface
{
    public function all(): Collection
    {
        return Song::query()->orderBy('title')->get();
    }

    public function search(?string $query, int $limit): Collection
    {
        return Song::query()
            ->when($query !== null && $query !== '', function ($builder) use ($query): void {
                $needle = '%'.mb_strtolower($query).'%';

                $builder->where(function ($inner) use ($needle): void {
                    $inner->whereRaw('LOWER(title) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(artist) LIKE ?', [$needle]);
                });
            })
            ->orderBy('title')
            ->limit($limit)
            ->get();
    }

    public function findById(int $id): ?Song
    {
        return Song::query()->find($id);
    }

    public function create(array $attributes): Song
    {
        return Song::query()->create($attributes);
    }

    public function update(Song $song, array $attributes): Song
    {
        $song->fill($attributes)->save();

        return $song;
    }

    public function delete(Song $song): void
    {
        $song->delete();
    }
}
