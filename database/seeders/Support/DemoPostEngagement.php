<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Application\DTOs\CreateCommentData;
use App\Modules\Feed\Application\Services\CommentService;
use App\Modules\Feed\Application\Services\InteractionService;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Support\Collection;

/**
 * Comments + reactions on a post — shared by DemoFeedStep (regular posts)
 * and DemoReelStep (is_reel posts), since both want the same engagement
 * shape and neither should own the other's logic.
 */
final class DemoPostEngagement
{
    private const REACTION_KEYS = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

    public function __construct(
        private readonly CommentService $comments,
        private readonly InteractionService $interactions,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     */
    public function addComments(Post $post, Collection $users): void
    {
        $commenters = $users
            ->reject(fn (User $u) => $u->id === $post->author_id)
            ->shuffle()
            ->take(fake()->numberBetween(0, 6));

        $topLevelIds = [];

        foreach ($commenters as $commenter) {
            $parentId = $topLevelIds !== [] && fake()->boolean(25)
                ? fake()->randomElement($topLevelIds)
                : null;

            $comment = $this->comments->create(new CreateCommentData(
                body: fake()->randomElement(DemoContent::commentTemplates()),
                postId: $post->id,
                parentId: $parentId,
                authorId: $commenter->id,
            ));

            if ($parentId === null) {
                $topLevelIds[] = $comment->id;
            }
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function addReactions(Post $post, Collection $users): void
    {
        $reactors = $users
            ->reject(fn (User $u) => $u->id === $post->author_id)
            ->shuffle()
            ->take(fake()->numberBetween(0, (int) round($users->count() * 0.4)));

        foreach ($reactors as $reactor) {
            $this->interactions->react('post', $post->id, $reactor->id, fake()->randomElement(self::REACTION_KEYS));
        }
    }
}
