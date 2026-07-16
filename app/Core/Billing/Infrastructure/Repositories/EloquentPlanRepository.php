<?php

declare(strict_types=1);

namespace App\Core\Billing\Infrastructure\Repositories;

use App\Core\Billing\Application\Contracts\PlanRepositoryInterface;
use App\Core\Billing\Domain\Models\Plan;
use Illuminate\Support\Collection;

final class EloquentPlanRepository implements PlanRepositoryInterface
{
    public function create(array $attributes): Plan
    {
        return Plan::query()->create($attributes);
    }

    public function findById(int $id): ?Plan
    {
        return Plan::query()->find($id);
    }

    public function findBySlug(string $slug): ?Plan
    {
        return Plan::query()->where('slug', $slug)->first();
    }

    public function update(Plan $plan, array $attributes): Plan
    {
        $plan->fill($attributes)->save();

        return $plan;
    }

    public function delete(Plan $plan): void
    {
        $plan->delete();
    }

    public function listActive(): Collection
    {
        return Plan::query()->where('is_active', true)->orderBy('price_cents')->get();
    }

    public function listAll(): Collection
    {
        return Plan::query()->orderBy('price_cents')->get();
    }
}
