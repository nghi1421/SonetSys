<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\Services;

use App\Modules\Menu\Application\Contracts\StaticPageRepositoryInterface;
use App\Modules\Menu\Application\DTOs\CreateStaticPageData;
use App\Modules\Menu\Application\DTOs\UpdateStaticPageData;
use App\Modules\Menu\Domain\Models\StaticPage;
use Illuminate\Support\Collection;

final class StaticPageService
{
    public function __construct(
        private readonly StaticPageRepositoryInterface $pages,
    ) {}

    /**
     * @return Collection<int, StaticPage>
     */
    public function list(): Collection
    {
        return $this->pages->list();
    }

    public function create(CreateStaticPageData $data): StaticPage
    {
        return $this->pages->create([
            'author_id' => $data->authorId,
            'title' => $data->title,
            'content' => $data->content,
        ]);
    }

    public function update(StaticPage $page, UpdateStaticPageData $data): StaticPage
    {
        return $this->pages->update($page, [
            'title' => $data->title,
            'content' => $data->content,
        ]);
    }

    public function delete(StaticPage $page): void
    {
        $this->pages->delete($page);
    }
}
