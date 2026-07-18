import type { AppNotification } from '../types'

type Translate = (key: string, params?: Record<string, unknown>) => string

export function describeNotification(notification: AppNotification, t: Translate): string {
  const actor = notification.actor.name ?? 'Someone'

  switch (notification.type) {
    case 'post.liked':
      return t('notifications.types.postLiked', { actor })
    case 'comment.liked':
      return t('notifications.types.commentLiked', { actor })
    case 'comment.posted':
      return t('notifications.types.commentPosted', { actor })
    case 'post.shared':
      return t('notifications.types.postShared', { actor })
    case 'user.followed':
      return t('notifications.types.userFollowed', { actor })
    case 'user.mentioned':
      return t('notifications.types.userMentioned', { actor })
    default:
      return t('notifications.types.default', { actor })
  }
}
