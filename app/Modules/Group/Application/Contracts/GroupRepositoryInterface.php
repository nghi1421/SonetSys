<?php

declare(strict_types=1);

namespace App\Modules\Group\Application\Contracts;

use App\Modules\Group\Domain\Models\Group;
use Illuminate\Support\Collection;

interface GroupRepositoryInterface
{
    public function create(array $attributes): Group;

    public function findById(int $id): ?Group;

    public function findBySlug(string $slug): ?Group;

    /**
     * @return Collection<int, Group>
     */
    public function list(): Collection;

    public function update(Group $group, array $attributes): Group;

    public function delete(Group $group): void;

    public function incrementMembersCount(int $groupId): void;

    public function decrementMembersCount(int $groupId): void;
}
