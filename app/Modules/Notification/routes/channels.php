<?php

use App\Core\Auth\Domain\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId;
});
