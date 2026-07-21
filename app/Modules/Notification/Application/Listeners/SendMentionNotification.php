<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Listeners;

use App\Modules\Feed\Domain\Events\UserMentioned;
use App\Modules\Notification\Application\DTOs\CreateNotificationData;
use App\Modules\Notification\Application\Services\NotificationService;
use App\Modules\Notification\Domain\Enums\NotificationType;

final class SendMentionNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handle(UserMentioned $event): void
    {
        foreach ($event->mentionedUserIds as $mentionedUserId) {
            $this->notifications->create(new CreateNotificationData(
                notifiableType: 'user',
                notifiableId: $mentionedUserId,
                actorId: $event->mentionedByUserId,
                type: NotificationType::UserMentioned,
                data: [
                    'mentionable_type' => $event->mentionableType,
                    'mentionable_id' => $event->mentionableId,
                    'post_id' => $event->postId,
                ],
            ));
        }
    }
}
