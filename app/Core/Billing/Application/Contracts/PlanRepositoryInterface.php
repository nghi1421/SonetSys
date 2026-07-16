<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Contracts;

use App\Core\Billing\Domain\Models\Plan;
use Illuminate\Support\Collection;

interface PlanRepositoryInterface
{
    public function create(array $attributes): Plan;

    public function findById(int $id): ?Plan;

    public function findBySlug(string $slug): ?Plan;

    public function update(Plan $plan, array $attributes): Plan;

    public function delete(Plan $plan): void;

    /**
     * @return Collection<int, Plan>
     */
    public function listActive(): Collection;

    /**
     * @return Collection<int, Plan>
     */
    public function listAll(): Collection;
}
