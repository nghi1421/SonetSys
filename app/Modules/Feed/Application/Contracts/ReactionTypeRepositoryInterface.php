<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Contracts;

use App\Modules\Feed\Domain\Models\ReactionType;
use Illuminate\Support\Collection;

interface ReactionTypeRepositoryInterface
{
    /**
     * @return Collection<int, ReactionType>
     */
    public function all(): Collection;

    public function findById(int $id): ?ReactionType;

    public function create(array $attributes): ReactionType;

    public function update(ReactionType $reactionType, array $attributes): ReactionType;

    public function delete(ReactionType $reactionType): void;
}
