<?php

declare(strict_types=1);

namespace App\Core\Storage\Application\Services;

use App\Core\Storage\Application\Contracts\MediaRepositoryInterface;
use App\Core\Storage\Domain\Enums\MediaType;
use App\Core\Storage\Domain\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class MediaService
{
    public function __construct(
        private readonly MediaRepositoryInterface $media,
        private readonly StorageService $storage,
    ) {}

    public function store(
        UploadedFile $file,
        int $tenantId,
        int $uploadedBy,
        MediaType $type,
        string $directory,
        ?Model $mediable = null,
    ): Media {
        $stored = $this->storage->store($file, $tenantId, $directory);

        return $this->attach($stored, $tenantId, $uploadedBy, $type, $file, $mediable);
    }

    /**
     * Records a Media row for a file that was already stored via
     * StorageService::store() — used when the mediable model (e.g. a Post)
     * doesn't exist yet at the time the file itself is stored.
     *
     * @param  array{disk: string, path: string}  $stored
     */
    public function attach(
        array $stored,
        int $tenantId,
        int $uploadedBy,
        MediaType $type,
        UploadedFile $file,
        ?Model $mediable = null,
    ): Media {
        return $this->media->create([
            'tenant_id' => $tenantId,
            'uploaded_by' => $uploadedBy,
            'mediable_type' => $mediable?->getMorphClass(),
            'mediable_id' => $mediable?->getKey(),
            'disk' => $stored['disk'],
            'type' => $type,
            'path' => $stored['path'],
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?? 0,
        ]);
    }

    public function delete(Media $media): void
    {
        $this->storage->delete((int) $media->tenant_id, $media->disk, $media->path);
        $this->media->delete($media);
    }

    /**
     * @return bool true if a Media row was found (and deleted) for this mediable
     */
    public function deleteForMediable(Model $mediable): bool
    {
        $media = $this->media->findForMediable($mediable->getMorphClass(), (int) $mediable->getKey());

        if ($media === null) {
            return false;
        }

        $this->delete($media);

        return true;
    }

    public function url(Media $media): string
    {
        return $this->storage->url((int) $media->tenant_id, $media->disk, $media->path);
    }

    public function findById(int $id): ?Media
    {
        return $this->media->findById($id);
    }

    public function listForTenant(int $tenantId, ?string $type, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->media->paginateForTenant($tenantId, $type, $perPage, $page);
    }
}
