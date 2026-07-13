<?php

use App\Core\Auth\Domain\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}.user.{userId}', function (User $user, int $tenantId, int $userId): bool {
    return $user->tenant_id === $tenantId && $user->id === $userId;
});
