<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Services;

use App\Modules\Notification\Application\Contracts\NotificationRepositoryInterface;
use App\Modules\Notification\Application\DTOs\CreateNotificationData;
use App\Modules\Notification\Domain\Events\NotificationCreated;
use App\Modules\Notification\Domain\Models\Notification;
use Illuminate\Support\Collection;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
    ) {}

    public function create(CreateNotificationData $data): Notification
    {
        $notification = $this->notifications->create([
            'tenant_id' => $data->tenantId,
            'notifiable_type' => $data->notifiableType,
            'notifiable_id' => $data->notifiableId,
            'actor_id' => $data->actorId,
            'type' => $data->type,
            'notifiable_data' => $data->data,
        ]);

        NotificationCreated::dispatch($notification);

        return $notification;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function listForUser(int $tenantId, int $userId, int $limit = 20): Collection
    {
        return $this->notifications->listForRecipient($tenantId, 'user', $userId, $limit);
    }

    public function markAsRead(Notification $notification): void
    {
        $this->notifications->markAsRead($notification);
    }

    public function markAllAsRead(int $tenantId, int $userId): void
    {
        $this->notifications->markAllAsRead($tenantId, 'user', $userId);
    }

    public function unreadCount(int $tenantId, int $userId): int
    {
        return $this->notifications->unreadCount($tenantId, 'user', $userId);
    }
}
