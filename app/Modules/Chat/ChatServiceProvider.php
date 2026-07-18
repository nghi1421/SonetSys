<?php

declare(strict_types=1);

namespace App\Modules\Chat;

use App\Modules\Chat\Application\Contracts\ConversationRepositoryInterface;
use App\Modules\Chat\Application\Contracts\MessageRepositoryInterface;
use App\Modules\Chat\Infrastructure\Repositories\EloquentConversationRepository;
use App\Modules\Chat\Infrastructure\Repositories\EloquentMessageRepository;
use Illuminate\Support\ServiceProvider;

final class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConversationRepositoryInterface::class, EloquentConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, EloquentMessageRepository::class);
    }
}
