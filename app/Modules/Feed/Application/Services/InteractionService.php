<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Application\Contracts\InteractionRepositoryInterface;
use App\Modules\Feed\Domain\Enums\InteractionType;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class InteractionService
{
    public function __construct(
        private readonly InteractionRepositoryInterface $interactions,
    ) {}

    /**
     * @return array{liked: bool, likes_count: int}
     */
    public function toggleLike(string $morphAlias, int $interactableId, int $userId, int $tenantId): array
    {
        $modelClass = Relation::getMorphedModel($morphAlias)
            ?? throw new InvalidArgumentException("Unknown interactable type [{$morphAlias}].");

        return DB::transaction(function () use ($morphAlias, $interactableId, $userId, $tenantId, $modelClass): array {
            $existing = $this->interactions->findExisting(
                $userId,
                $morphAlias,
                $interactableId,
                InteractionType::Like->value,
            );

            if ($existing !== null) {
                $this->interactions->delete($existing);
                $modelClass::whereKey($interactableId)->decrement('likes_count');
                $liked = false;
            } else {
                $this->interactions->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'interactable_type' => $morphAlias,
                    'interactable_id' => $interactableId,
                    'type' => InteractionType::Like->value,
                ]);
                $modelClass::whereKey($interactableId)->increment('likes_count');
                $liked = true;
            }

            return [
                'liked' => $liked,
                'likes_count' => (int) $modelClass::whereKey($interactableId)->value('likes_count'),
            ];
        });
    }
}
