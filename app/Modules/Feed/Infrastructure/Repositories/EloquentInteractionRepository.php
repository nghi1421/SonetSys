<?php

declare(strict_types=1);

namespace App\Modules\Feed\Infrastructure\Repositories;

use App\Modules\Feed\Application\Contracts\InteractionRepositoryInterface;
use App\Modules\Feed\Domain\Models\Interaction;

final class EloquentInteractionRepository implements InteractionRepositoryInterface
{
    public function findExisting(int $userId, string $interactableType, int $interactableId): ?Interaction
    {
        return Interaction::query()
            ->where('user_id', $userId)
            ->where('interactable_type', $interactableType)
            ->where('interactable_id', $interactableId)
            ->first();
    }

    public function create(array $attributes): Interaction
    {
        return Interaction::query()->create($attributes);
    }

    public function update(Interaction $interaction, string $type): void
    {
        $interaction->update(['type' => $type]);
    }

    public function delete(Interaction $interaction): void
    {
        $interaction->delete();
    }

    public function myReactionsAmong(int $userId, string $interactableType, array $interactableIds): array
    {
        if ($interactableIds === []) {
            return [];
        }

        return Interaction::query()
            ->where('user_id', $userId)
            ->where('interactable_type', $interactableType)
            ->whereIn('interactable_id', $interactableIds)
            ->pluck('type', 'interactable_id')
            ->all();
    }
}
