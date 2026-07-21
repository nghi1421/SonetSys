<?php

declare(strict_types=1);

namespace App\Modules\Feed\Infrastructure\Repositories;

use App\Modules\Feed\Application\Contracts\ReactionTypeRepositoryInterface;
use App\Modules\Feed\Domain\Models\ReactionType;
use Illuminate\Support\Collection;

final class EloquentReactionTypeRepository implements ReactionTypeRepositoryInterface
{
    public function all(): Collection
    {
        return ReactionType::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function findById(int $id): ?ReactionType
    {
        return ReactionType::query()->find($id);
    }

    public function create(array $attributes): ReactionType
    {
        return ReactionType::query()->create($attributes);
    }

    public function update(ReactionType $reactionType, array $attributes): ReactionType
    {
        $reactionType->fill($attributes)->save();

        return $reactionType;
    }

    public function delete(ReactionType $reactionType): void
    {
        $reactionType->delete();
    }
}
