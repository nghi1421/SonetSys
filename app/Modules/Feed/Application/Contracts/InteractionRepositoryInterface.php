<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Contracts;

use App\Modules\Feed\Domain\Models\Interaction;

interface InteractionRepositoryInterface
{
    public function findExisting(int $userId, string $interactableType, int $interactableId, string $type): ?Interaction;

    public function create(array $attributes): Interaction;

    public function delete(Interaction $interaction): void;

    /**
     * @param  list<int>  $interactableIds
     * @return list<int>
     */
    public function likedInteractableIds(int $userId, string $interactableType, array $interactableIds): array;
}
