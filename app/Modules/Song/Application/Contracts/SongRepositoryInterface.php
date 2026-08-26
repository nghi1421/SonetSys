<?php

declare(strict_types=1);

namespace App\Modules\Song\Application\Contracts;

use App\Modules\Song\Domain\Models\Song;
use Illuminate\Support\Collection;

interface SongRepositoryInterface
{
    /**
     * @return Collection<int, Song>
     */
    public function all(): Collection;

    /**
     * @return Collection<int, Song>
     */
    public function search(?string $query, int $limit): Collection;

    public function findById(int $id): ?Song;

    public function create(array $attributes): Song;

    public function update(Song $song, array $attributes): Song;

    public function delete(Song $song): void;
}
