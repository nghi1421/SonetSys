<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Listeners;

use App\Modules\Feed\Domain\Events\ContentLiked;
use App\Modules\Notification\Application\DTOs\CreateNotificationData;
use App\Modules\Notification\Application\Services\NotificationService;
use App\Modules\Notification\Domain\Enums\NotificationType;

final class SendLikeNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handle(ContentLiked $event): void
    {
        $this->notifications->create(new CreateNotificationData(
            notifiableType: 'user',
            notifiableId: $event->contentAuthorId,
            actorId: $event->likedByUserId,
            type: $event->interactableType === 'post' ? NotificationType::PostLiked : NotificationType::CommentLiked,
            data: [
                'interactable_type' => $event->interactableType,
                'interactable_id' => $event->interactableId,
                'reaction_type' => $event->reactionType,
                'post_id' => $event->postId,
            ],
        ));
    }
}
