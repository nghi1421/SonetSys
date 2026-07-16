<?php

declare(strict_types=1);

namespace App\Core\Storage\Infrastructure\Repositories;

use App\Core\Storage\Application\Contracts\MediaRepositoryInterface;
use App\Core\Storage\Domain\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentMediaRepository implements MediaRepositoryInterface
{
    public function create(array $attributes): Media
    {
        return Media::query()->create($attributes);
    }

    public function findById(int $id): ?Media
    {
        return Media::query()->find($id);
    }

    public function findForMediable(string $mediableType, int $mediableId): ?Media
    {
        return Media::query()
            ->where('mediable_type', $mediableType)
            ->where('mediable_id', $mediableId)
            ->first();
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }

    public function paginateForTenant(int $tenantId, ?string $type, int $perPage, int $page): LengthAwarePaginator
    {
        return Media::query()
            ->where('tenant_id', $tenantId)
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->with('uploader')
            ->orderByDesc('created_at')
            ->paginate($perPage, page: $page);
    }
}
