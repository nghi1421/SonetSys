<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Core\Storage\Application\Services\MediaService;
use App\Core\Storage\Application\Services\StorageService;
use App\Core\Storage\Domain\Enums\MediaType;
use App\Modules\Feed\Application\Contracts\ReactionTypeRepositoryInterface;
use App\Modules\Feed\Application\DTOs\CreateReactionTypeData;
use App\Modules\Feed\Application\DTOs\UpdateReactionTypeData;
use App\Modules\Feed\Domain\Models\ReactionType;
use Illuminate\Support\Collection;

final class ReactionTypeService
{
    private const DEFAULTS = [
        ['key' => 'like', 'label' => 'Like', 'emoji' => '👍', 'sort_order' => 0],
        ['key' => 'love', 'label' => 'Love', 'emoji' => '❤️', 'sort_order' => 1],
        ['key' => 'haha', 'label' => 'Haha', 'emoji' => '😆', 'sort_order' => 2],
        ['key' => 'wow', 'label' => 'Wow', 'emoji' => '😮', 'sort_order' => 3],
        ['key' => 'sad', 'label' => 'Sad', 'emoji' => '😢', 'sort_order' => 4],
        ['key' => 'angry', 'label' => 'Angry', 'emoji' => '😠', 'sort_order' => 5],
    ];

    public function __construct(
        private readonly ReactionTypeRepositoryInterface $reactionTypes,
        private readonly StorageService $storage,
        private readonly MediaService $media,
    ) {}

    /**
     * @return Collection<int, ReactionType>
     */
    public function all(): Collection
    {
        return $this->reactionTypes->all();
    }

    public function create(CreateReactionTypeData $data, int $uploadedBy): ReactionType
    {
        $attributes = [
            'key' => $data->key,
            'label' => $data->label,
            'emoji' => $data->emoji,
            'sort_order' => $data->sortOrder,
        ];

        if ($data->icon !== null) {
            $stored = $this->storage->store($data->icon, 'reactions');
            $attributes['icon_disk'] = $stored['disk'];
            $attributes['icon_path'] = $stored['path'];
            $attributes['icon_url'] = $this->storage->url($stored['disk'], $stored['path']);
        }

        $reactionType = $this->reactionTypes->create($attributes);

        if ($data->icon !== null) {
            $this->media->attach($stored, $uploadedBy, MediaType::Image, $data->icon, $reactionType);
        }

        return $reactionType;
    }

    public function update(ReactionType $reactionType, UpdateReactionTypeData $data, int $uploadedBy): ReactionType
    {
        $attributes = [
            'label' => $data->label,
            'emoji' => $data->emoji,
            'sort_order' => $data->sortOrder,
        ];

        if ($data->icon !== null) {
            $oldDisk = $reactionType->icon_disk;
            $oldPath = $reactionType->icon_path;

            $stored = $this->storage->store($data->icon, 'reactions');
            $attributes['icon_disk'] = $stored['disk'];
            $attributes['icon_path'] = $stored['path'];
            $attributes['icon_url'] = $this->storage->url($stored['disk'], $stored['path']);

            $reactionType = $this->reactionTypes->update($reactionType, $attributes);

            $this->media->attach($stored, $uploadedBy, MediaType::Image, $data->icon, $reactionType);

            if ($oldDisk !== null && $oldPath !== null) {
                $this->storage->delete($oldDisk, $oldPath);
            }

            return $reactionType;
        }

        return $this->reactionTypes->update($reactionType, $attributes);
    }

    public function delete(ReactionType $reactionType): void
    {
        if ($reactionType->icon_disk !== null && $reactionType->icon_path !== null) {
            $this->storage->delete($reactionType->icon_disk, $reactionType->icon_path);
        }

        $this->reactionTypes->delete($reactionType);
    }

    public function seedDefaults(): void
    {
        if ($this->reactionTypes->all()->isNotEmpty()) {
            return;
        }

        foreach (self::DEFAULTS as $default) {
            $this->reactionTypes->create($default);
        }
    }
}
