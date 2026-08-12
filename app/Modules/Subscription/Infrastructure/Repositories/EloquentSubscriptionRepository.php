<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Repositories;

use App\Modules\Subscription\Application\Contracts\SubscriptionRepositoryInterface;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Models\UserSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function create(array $attributes): UserSubscription
    {
        return UserSubscription::query()->create($attributes);
    }

    public function findById(int $id): ?UserSubscription
    {
        return UserSubscription::query()->find($id);
    }

    public function findActiveForUser(int $userId): ?UserSubscription
    {
        return UserSubscription::query()
            ->where('user_id', $userId)
            ->where('status', SubscriptionStatus::Active)
            ->first();
    }

    public function update(UserSubscription $subscription, array $attributes): UserSubscription
    {
        $subscription->update($attributes);

        return $subscription->refresh();
    }

    public function dueForRenewal(): Collection
    {
        return UserSubscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('current_period_end', '<=', now())
            ->get();
    }

    public function historyForUser(int $userId): Collection
    {
        return UserSubscription::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();
    }

    public function allPaginated(int $page, int $perPage): LengthAwarePaginator
    {
        return UserSubscription::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate($perPage, page: $page);
    }
}
