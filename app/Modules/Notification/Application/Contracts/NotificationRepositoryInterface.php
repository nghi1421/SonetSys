<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Contracts;

use App\Modules\Notification\Domain\Models\Notification;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function create(array $attributes): Notification;

    public function findById(string $id): ?Notification;

    /**
     * @return Collection<int, Notification>
     */
    public function listForRecipient(int $tenantId, string $notifiableType, int $notifiableId, int $limit): Collection;

    public function markAsRead(Notification $notification): void;

    public function markAllAsRead(int $tenantId, string $notifiableType, int $notifiableId): void;

    public function unreadCount(int $tenantId, string $notifiableType, int $notifiableId): int;
}
