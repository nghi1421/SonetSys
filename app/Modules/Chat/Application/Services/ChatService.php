<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Services;

use App\Modules\Block\Application\Services\BlockService;
use App\Modules\Chat\Application\Contracts\ConversationRepositoryInterface;
use App\Modules\Chat\Application\Contracts\MessageRepositoryInterface;
use App\Modules\Chat\Domain\Events\MessageSent;
use App\Modules\Chat\Domain\Models\Conversation;
use App\Modules\Chat\Domain\Models\Message;
use App\Modules\Follow\Application\Services\FollowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ChatService
{
    private const DEFAULT_LIMIT = 30;

    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MessageRepositoryInterface $messages,
        private readonly FollowService $follows,
        private readonly BlockService $blocks,
    ) {}

    public function startOrGetConversation(int $viewerId, int $otherUserId): Conversation
    {
        if ($viewerId === $otherUserId) {
            throw ValidationException::withMessages([
                'user' => 'You cannot message yourself.',
            ]);
        }

        if ($this->blocks->isBlockedEitherWay($viewerId, $otherUserId)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot message this user.',
            ]);
        }

        if (! $this->follows->isMutual($viewerId, $otherUserId)) {
            throw ValidationException::withMessages([
                'user' => 'You can only message users who follow you back.',
            ]);
        }

        return $this->conversations->findBetween($viewerId, $otherUserId)
            ?? $this->conversations->createBetween($viewerId, $otherUserId);
    }

    public function sendMessage(int $conversationId, int $senderId, string $body): Message
    {
        $conversation = $this->findConversationOrFail($conversationId);
        $this->assertParticipant($conversation, $senderId);

        // startOrGetConversation() only checks block state at conversation
        // creation — a block could happen afterward, so it's re-checked here
        // on every send as its own hard-stop guard.
        $otherUserId = $conversation->otherParticipantId($senderId);
        if ($this->blocks->isBlockedEitherWay($senderId, $otherUserId)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot message this user.',
            ]);
        }

        $message = $this->messages->create($conversationId, $senderId, $body);

        $this->conversations->touchLastMessageAt($conversation, $message->created_at);
        // The sender has implicitly "seen" the message they just sent, so
        // their own unread state shouldn't flip on the back of it.
        $this->conversations->markRead($conversation, $senderId);

        MessageSent::dispatch($message, $conversation->otherParticipantId($senderId));

        return $message;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function listConversations(int $userId): Collection
    {
        return $this->conversations->listForUser($userId)
            ->each(function (Conversation $conversation) use ($userId): void {
                $otherId = $conversation->otherParticipantId($userId);
                $conversation->other_participant = $conversation->user_one_id === $otherId
                    ? $conversation->userOne
                    : $conversation->userTwo;
                $conversation->is_unread = $this->isUnreadFor($conversation, $userId);
            });
    }

    /**
     * @return array{items: Collection<int, Message>, next_cursor: ?string}
     */
    public function messagesFor(int $conversationId, int $viewerId, ?string $cursor, int $limit = self::DEFAULT_LIMIT): array
    {
        $conversation = $this->findConversationOrFail($conversationId);
        $this->assertParticipant($conversation, $viewerId);

        [$afterCreatedAt, $afterId] = $this->decodeCursor($cursor);

        // Newest-first page, mirroring EloquentPostRepository::cursorPaginate.
        $messages = $this->messages->cursorPaginate($conversationId, $afterCreatedAt, $afterId, $limit);

        $nextCursor = null;
        if ($messages->count() === $limit) {
            $oldest = $messages->last();
            $nextCursor = $this->encodeCursor($oldest->created_at, $oldest->id);
        }

        return [
            // Reversed to chronological ascending (oldest first) for rendering.
            'items' => $messages->reverse()->values(),
            'next_cursor' => $nextCursor,
        ];
    }

    public function markThreadRead(Conversation $conversation, int $viewerId): void
    {
        $this->conversations->markRead($conversation, $viewerId);
    }

    public function unreadCount(int $userId): int
    {
        return $this->conversations->unreadCountFor($userId);
    }

    public function assertParticipant(Conversation $conversation, int $userId): void
    {
        if (! $conversation->hasParticipant($userId)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }
    }

    private function findConversationOrFail(int $conversationId): Conversation
    {
        return $this->conversations->findById($conversationId) ?? throw new ModelNotFoundException;
    }

    private function isUnreadFor(Conversation $conversation, int $userId): bool
    {
        if ($conversation->last_message_at === null) {
            return false;
        }

        $lastReadAt = $conversation->user_one_id === $userId
            ? $conversation->user_one_last_read_at
            : $conversation->user_two_last_read_at;

        return $lastReadAt === null || $conversation->last_message_at->greaterThan($lastReadAt);
    }

    /**
     * @return array{0: ?Carbon, 1: ?int}
     */
    private function decodeCursor(?string $cursor): array
    {
        if ($cursor === null) {
            return [null, null];
        }

        $decoded = base64_decode($cursor, true);

        if ($decoded === false || ! str_contains($decoded, '|')) {
            return [null, null];
        }

        [$createdAt, $id] = explode('|', $decoded, 2);

        return [Carbon::parse($createdAt), (int) $id];
    }

    private function encodeCursor(\DateTimeInterface $createdAt, int $id): string
    {
        return base64_encode($createdAt->format(DATE_ATOM).'|'.$id);
    }
}
