<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Repositories;

use App\Modules\Notification\Application\Contracts\NotificationRepositoryInterface;
use App\Modules\Notification\Domain\Models\Notification;
use Illuminate\Support\Collection;

final class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $attributes): Notification
    {
        return Notification::query()->create($attributes);
    }

    public function findById(string $id): ?Notification
    {
        return Notification::query()->find($id);
    }

    public function listForRecipient(string $notifiableType, int $notifiableId, int $limit): Collection
    {
        return Notification::query()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->with('actor')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function markAsRead(Notification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAllAsRead(string $notifiableType, int $notifiableId): void
    {
        Notification::query()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCount(string $notifiableType, int $notifiableId): int
    {
        return Notification::query()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->whereNull('read_at')
            ->count();
    }
}
