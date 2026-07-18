<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Enums;

enum NotificationType: string
{
    case PostLiked = 'post.liked';
    case CommentLiked = 'comment.liked';
    case CommentPosted = 'comment.posted';
    case PostShared = 'post.shared';
    case UserFollowed = 'user.followed';
    case UserMentioned = 'user.mentioned';
}
