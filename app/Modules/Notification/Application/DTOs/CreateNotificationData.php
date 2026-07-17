<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\DTOs;

use App\Modules\Notification\Domain\Enums\NotificationType;

final readonly class CreateNotificationData
{
    public function __construct(
        public string $notifiableType,
        public int $notifiableId,
        public ?int $actorId,
        public NotificationType $type,
        public array $data = [],
    ) {}
}
