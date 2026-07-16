<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\Contracts;

use App\Modules\Menu\Domain\Models\StaticPage;
use Illuminate\Support\Collection;

interface StaticPageRepositoryInterface
{
    public function create(array $attributes): StaticPage;

    public function findById(int $id): ?StaticPage;

    /**
     * @return Collection<int, StaticPage>
     */
    public function list(): Collection;

    public function update(StaticPage $page, array $attributes): StaticPage;

    public function delete(StaticPage $page): void;
}
