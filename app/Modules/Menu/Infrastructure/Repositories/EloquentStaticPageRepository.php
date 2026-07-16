<?php

declare(strict_types=1);

namespace App\Modules\Menu\Infrastructure\Repositories;

use App\Modules\Menu\Application\Contracts\StaticPageRepositoryInterface;
use App\Modules\Menu\Domain\Models\StaticPage;
use Illuminate\Support\Collection;

final class EloquentStaticPageRepository implements StaticPageRepositoryInterface
{
    public function create(array $attributes): StaticPage
    {
        return StaticPage::query()->create($attributes);
    }

    public function findById(int $id): ?StaticPage
    {
        return StaticPage::query()->find($id);
    }

    public function list(): Collection
    {
        return StaticPage::query()->orderByDesc('created_at')->get();
    }

    public function update(StaticPage $page, array $attributes): StaticPage
    {
        $page->fill($attributes)->save();

        return $page;
    }

    public function delete(StaticPage $page): void
    {
        $page->delete();
    }
}
