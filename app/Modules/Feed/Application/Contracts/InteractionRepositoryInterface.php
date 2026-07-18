<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Contracts;

use App\Modules\Feed\Domain\Enums\InteractionType;
use App\Modules\Feed\Domain\Models\Interaction;

interface InteractionRepositoryInterface
{
    public function findExisting(int $userId, string $interactableType, int $interactableId): ?Interaction;

    public function create(array $attributes): Interaction;

    public function update(Interaction $interaction, InteractionType $type): void;

    public function delete(Interaction $interaction): void;

    /**
     * @param  list<int>  $interactableIds
     * @return array<int, string>
     */
    public function myReactionsAmong(int $userId, string $interactableType, array $interactableIds): array;
}
