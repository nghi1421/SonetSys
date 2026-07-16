<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Listeners;

use App\Modules\Feed\Domain\Events\CommentPosted;
use App\Modules\Notification\Application\DTOs\CreateNotificationData;
use App\Modules\Notification\Application\Services\NotificationService;
use App\Modules\Notification\Domain\Enums\NotificationType;

final class SendCommentNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handle(CommentPosted $event): void
    {
        $recipientId = $event->parentCommentAuthorId ?? $event->postAuthorId;

        if ($recipientId === $event->authorId) {
            return;
        }

        $this->notifications->create(new CreateNotificationData(
            notifiableType: 'user',
            notifiableId: $recipientId,
            actorId: $event->authorId,
            type: NotificationType::CommentPosted,
            data: [
                'post_id' => $event->postId,
                'comment_id' => $event->commentId,
            ],
        ));
    }
}
