<?php

declare(strict_types=1);

namespace App\Core\Storage\Application\Contracts;

use App\Core\Storage\Domain\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MediaRepositoryInterface
{
    public function create(array $attributes): Media;

    public function findById(int $id): ?Media;

    public function findForMediable(string $mediableType, int $mediableId): ?Media;

    public function delete(Media $media): void;

    public function paginateForTenant(int $tenantId, ?string $type, int $perPage, int $page): LengthAwarePaginator;
}
