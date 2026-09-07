<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Group\Application\DTOs\CreateGroupData;
use App\Modules\Group\Application\Services\GroupService;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use App\Modules\Group\Domain\Models\Group;
use Illuminate\Support\Collection;

final class DemoGroupStep
{
    private const MIN_MEMBERS = 14;

    private const MAX_MEMBERS = 28;

    public function __construct(
        private readonly GroupService $groups,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, Group>
     */
    public function run(Collection $users): Collection
    {
        $created = collect();

        foreach (DemoContent::groups() as $definition) {
            $owner = $users->random();
            $visibility = $definition['visibility'] === 'private'
                ? GroupVisibility::Private
                : GroupVisibility::Public;

            $group = $this->groups->create(new CreateGroupData(
                name: $definition['name'],
                description: $definition['description'],
                visibility: $visibility,
                ownerId: $owner->id,
            ));

            $this->populateMembers($group, $users, $owner);

            $created->push($group);
        }

        return $created;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function populateMembers(Group $group, Collection $users, User $owner): void
    {
        $candidates = $users
            ->reject(fn (User $user) => $user->id === $owner->id)
            ->shuffle()
            ->take(fake()->numberBetween(self::MIN_MEMBERS, self::MAX_MEMBERS));

        $adminPromotions = 0;

        foreach ($candidates as $candidate) {
            $member = $this->groups->requestJoin($group, $candidate->id);

            if ($group->visibility === GroupVisibility::Private) {
                // Leave ~30% of private-group requests pending, approve the rest —
                // an active group has a visible queue, not an empty one.
                if (fake()->boolean(70)) {
                    $this->groups->approveMember($group, $candidate->id);
                    $member = $this->groups->membershipFor($group, $candidate->id);
                }
            }

            if ($member !== null
                && $member->status->value === 'approved'
                && $adminPromotions < 2
                && fake()->boolean(15)
            ) {
                $this->groups->promoteToAdmin($group, $candidate->id);
                $adminPromotions++;
            }
        }
    }
}
