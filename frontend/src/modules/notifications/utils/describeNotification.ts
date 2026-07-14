import type { AppNotification } from '../types'

export function describeNotification(notification: AppNotification): string {
  const actor = notification.actor.name ?? 'Someone'

  switch (notification.type) {
    case 'post.liked':
      return `${actor} liked your post`
    case 'comment.liked':
      return `${actor} liked your comment`
    case 'comment.posted':
      return `${actor} commented on your post`
    case 'post.shared':
      return `${actor} shared your post`
    default:
      return `${actor} interacted with your content`
  }
}
