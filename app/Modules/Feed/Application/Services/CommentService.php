<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Application\Contracts\CommentRepositoryInterface;
use App\Modules\Feed\Application\Contracts\InteractionRepositoryInterface;
use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Application\DTOs\CreateCommentData;
use App\Modules\Feed\Application\DTOs\UpdateCommentData;
use App\Modules\Feed\Domain\Events\CommentPosted;
use App\Modules\Feed\Domain\Events\UserMentioned;
use App\Modules\Feed\Domain\Models\Comment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly PostRepositoryInterface $posts,
        private readonly InteractionRepositoryInterface $interactions,
        private readonly HashtagService $hashtags,
        private readonly MentionService $mentions,
    ) {}

    public function create(CreateCommentData $data): Comment
    {
        $mentionedUserIds = $this->mentions->filterRecipients($data->mentionedUserIds, $data->authorId);

        return DB::transaction(function () use ($data, $mentionedUserIds): Comment {
            $parentId = $data->parentId;
            $directParentAuthorId = null;

            if ($parentId !== null) {
                $parent = $this->comments->findById($parentId);
                // Capture who the user actually clicked "reply" on, before
                // flattening — that's the correct notification recipient.
                $directParentAuthorId = $parent?->author_id;

                // Depth cap = 1: a reply to a reply flattens onto the original parent.
                if ($parent !== null && $parent->parent_id !== null) {
                    $parentId = $parent->parent_id;
                }
            }

            $comment = $this->comments->create([
                'post_id' => $data->postId,
                'parent_id' => $parentId,
                'author_id' => $data->authorId,
                'body' => $data->body,
            ]);

            $this->posts->incrementCommentsCount($data->postId);

            $post = $this->posts->findById($data->postId);

            $this->hashtags->extractAndAttach($data->body, $comment);

            if ($mentionedUserIds !== []) {
                $comment->mentions()->sync($mentionedUserIds);
            }

            CommentPosted::dispatch(
                $comment->id,
                $data->postId,
                $data->authorId,
                $directParentAuthorId,
                (int) $post->author_id,
            );

            if ($mentionedUserIds !== []) {
                UserMentioned::dispatch('comment', $comment->id, $data->authorId, $mentionedUserIds);
            }

            return $comment;
        });
    }

    /**
     * @return Collection<int, Comment>
     */
    public function listForPost(int $postId, int $viewerId): Collection
    {
        $comments = $this->comments->listForPost($postId);
        $this->markReactionByViewer($comments, $viewerId);

        return $comments;
    }

    /**
     * @param  Collection<int, Comment>  $comments
     */
    private function markReactionByViewer(Collection $comments, int $viewerId): void
    {
        $myReactions = $this->interactions->myReactionsAmong($viewerId, 'comment', $comments->pluck('id')->all());

        $comments->each(function (Comment $comment) use ($myReactions): void {
            $comment->my_reaction = $myReactions[$comment->id] ?? null;
        });
    }

    public function update(Comment $comment, UpdateCommentData $data): Comment
    {
        return $this->comments->update($comment, ['body' => $data->body]);
    }

    public function delete(Comment $comment): void
    {
        $this->comments->delete($comment);
        $this->posts->decrementCommentsCount($comment->post_id);
    }
}
