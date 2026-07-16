<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Services;

use App\Core\Billing\Application\Contracts\PlanRepositoryInterface;
use App\Core\Billing\Application\DTOs\CreatePlanData;
use App\Core\Billing\Application\DTOs\UpdatePlanData;
use App\Core\Billing\Domain\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class PlanService
{
    public function __construct(
        private readonly PlanRepositoryInterface $plans,
    ) {}

    public function create(CreatePlanData $data): Plan
    {
        return $this->plans->create([
            'name' => $data->name,
            'slug' => $data->slug,
            'price_cents' => $data->priceCents,
            'interval' => $data->interval,
            'max_users' => $data->maxUsers,
            'features' => $data->features,
            'is_active' => true,
        ]);
    }

    public function update(Plan $plan, UpdatePlanData $data): Plan
    {
        return $this->plans->update($plan, [
            'name' => $data->name,
            'price_cents' => $data->priceCents,
            'interval' => $data->interval,
            'max_users' => $data->maxUsers,
            'features' => $data->features,
            'is_active' => $data->isActive,
        ]);
    }

    public function delete(Plan $plan): void
    {
        if ($plan->subscriptions()->exists()) {
            throw ValidationException::withMessages([
                'plan' => ['This plan has active subscriptions and cannot be deleted. Deactivate it instead.'],
            ]);
        }

        $this->plans->delete($plan);
    }

    public function findById(int $id): ?Plan
    {
        return $this->plans->findById($id);
    }

    /**
     * @return Collection<int, Plan>
     */
    public function listActive(): Collection
    {
        return $this->plans->listActive();
    }

    /**
     * @return Collection<int, Plan>
     */
    public function listAll(): Collection
    {
        return $this->plans->listAll();
    }
}
