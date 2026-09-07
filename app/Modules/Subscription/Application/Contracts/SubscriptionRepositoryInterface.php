<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Contracts;

use App\Modules\Subscription\Domain\Models\UserSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SubscriptionRepositoryInterface
{
    public function create(array $attributes): UserSubscription;

    public function findById(int $id): ?UserSubscription;

    public function findActiveForUser(int $userId): ?UserSubscription;

    public function update(UserSubscription $subscription, array $attributes): UserSubscription;

    /**
     * @return Collection<int, UserSubscription>
     */
    public function dueForRenewal(): Collection;

    /**
     * @return Collection<int, UserSubscription>
     */
    public function historyForUser(int $userId): Collection;

    public function allPaginated(int $page, int $perPage): LengthAwarePaginator;
}
