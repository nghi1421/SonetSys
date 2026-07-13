<?php

declare(strict_types=1);

namespace App\Modules\Feed;

use App\Modules\Feed\Application\Contracts\CommentRepositoryInterface;
use App\Modules\Feed\Application\Contracts\InteractionRepositoryInterface;
use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Feed\Infrastructure\Repositories\EloquentCommentRepository;
use App\Modules\Feed\Infrastructure\Repositories\EloquentInteractionRepository;
use App\Modules\Feed\Infrastructure\Repositories\EloquentPostRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class FeedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PostRepositoryInterface::class, EloquentPostRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, EloquentCommentRepository::class);
        $this->app->bind(InteractionRepositoryInterface::class, EloquentInteractionRepository::class);
    }

    public function boot(): void
    {
        // morphMap() (not enforceMorphMap()) — enforcing would require every
        // polymorphic relation app-wide to be mapped, including Sanctum's own
        // internal `tokenable` morph on User, which this module doesn't own.
        Relation::morphMap([
            'post' => Post::class,
            'comment' => Comment::class,
        ]);
    }
}
