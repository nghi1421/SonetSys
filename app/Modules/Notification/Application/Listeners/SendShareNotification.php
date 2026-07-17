<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Listeners;

use App\Modules\Feed\Domain\Events\PostShared;
use App\Modules\Notification\Application\DTOs\CreateNotificationData;
use App\Modules\Notification\Application\Services\NotificationService;
use App\Modules\Notification\Domain\Enums\NotificationType;

final class SendShareNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handle(PostShared $event): void
    {
        $this->notifications->create(new CreateNotificationData(
            notifiableType: 'user',
            notifiableId: $event->originalAuthorId,
            actorId: $event->sharedByUserId,
            type: NotificationType::PostShared,
            data: [
                'post_id' => $event->originalPostId,
                'share_post_id' => $event->sharePostId,
            ],
        ));
    }
}
