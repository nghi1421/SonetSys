<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Listeners;

use App\Modules\Follow\Domain\Events\UserFollowed;
use App\Modules\Notification\Application\DTOs\CreateNotificationData;
use App\Modules\Notification\Application\Services\NotificationService;
use App\Modules\Notification\Domain\Enums\NotificationType;

final class SendFollowNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handle(UserFollowed $event): void
    {
        $this->notifications->create(new CreateNotificationData(
            notifiableType: 'user',
            notifiableId: $event->followedId,
            actorId: $event->followerId,
            type: NotificationType::UserFollowed,
            data: [],
        ));
    }
}
