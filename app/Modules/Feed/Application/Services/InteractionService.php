<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Application\Contracts\InteractionRepositoryInterface;
use App\Modules\Feed\Domain\Events\ContentLiked;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class InteractionService
{
    public function __construct(
        private readonly InteractionRepositoryInterface $interactions,
    ) {}

    /**
     * @return array{my_reaction: ?string, likes_count: int}
     */
    public function react(string $morphAlias, int $interactableId, int $userId, string $type): array
    {
        $modelClass = Relation::getMorphedModel($morphAlias)
            ?? throw new InvalidArgumentException("Unknown interactable type [{$morphAlias}].");

        return DB::transaction(function () use ($morphAlias, $interactableId, $userId, $modelClass, $type): array {
            $existing = $this->interactions->findExisting($userId, $morphAlias, $interactableId);

            if ($existing === null) {
                $this->interactions->create([
                    'user_id' => $userId,
                    'interactable_type' => $morphAlias,
                    'interactable_id' => $interactableId,
                    'type' => $type,
                ]);
                $modelClass::whereKey($interactableId)->increment('likes_count');
                $myReaction = $type;

                $authorId = (int) $modelClass::whereKey($interactableId)->value('author_id');
                if ($authorId !== $userId) {
                    $postId = $morphAlias === 'comment'
                        ? (int) $modelClass::whereKey($interactableId)->value('post_id')
                        : $interactableId;
                    ContentLiked::dispatch($morphAlias, $interactableId, $userId, $authorId, $type, $postId);
                }
            } elseif ($existing->type === $type) {
                $this->interactions->delete($existing);
                $modelClass::whereKey($interactableId)->decrement('likes_count');
                $myReaction = null;
            } else {
                $this->interactions->update($existing, $type);
                $myReaction = $type;
            }

            return [
                'my_reaction' => $myReaction,
                'likes_count' => (int) $modelClass::whereKey($interactableId)->value('likes_count'),
            ];
        });
    }
}
