<?php

declare(strict_types=1);

namespace App\Modules\Notification;

use App\Modules\Feed\Domain\Events\CommentPosted;
use App\Modules\Feed\Domain\Events\ContentLiked;
use App\Modules\Feed\Domain\Events\PostShared;
use App\Modules\Feed\Domain\Events\UserMentioned;
use App\Modules\Follow\Domain\Events\UserFollowed;
use App\Modules\Notification\Application\Contracts\NotificationRepositoryInterface;
use App\Modules\Notification\Application\Listeners\SendCommentNotification;
use App\Modules\Notification\Application\Listeners\SendFollowNotification;
use App\Modules\Notification\Application\Listeners\SendLikeNotification;
use App\Modules\Notification\Application\Listeners\SendMentionNotification;
use App\Modules\Notification\Application\Listeners\SendShareNotification;
use App\Modules\Notification\Infrastructure\Repositories\EloquentNotificationRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
    }

    public function boot(): void
    {
        // Broadcasting auth route (with auth:sanctum) is registered in
        // bootstrap/app.php via withBroadcasting() — not here, since a
        // provider's boot() runs before the framework's own app->booted()
        // broadcasting registration and would just get overwritten.

        // Notification listens to Feed's events — Feed never references
        // Notification, so Feed keeps working if this module is disabled.
        Event::listen(ContentLiked::class, SendLikeNotification::class);
        Event::listen(CommentPosted::class, SendCommentNotification::class);
        Event::listen(PostShared::class, SendShareNotification::class);
        Event::listen(UserFollowed::class, SendFollowNotification::class);
        Event::listen(UserMentioned::class, SendMentionNotification::class);
    }
}
