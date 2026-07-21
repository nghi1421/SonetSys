import type { RouteLocationRaw } from 'vue-router'
import type { AppNotification } from '../types'

export function resolveNotificationRoute(notification: AppNotification): RouteLocationRaw | null {
  const postId = notification.data.post_id
  const sharePostId = notification.data.share_post_id

  switch (notification.type) {
    case 'post.liked':
    case 'comment.liked':
    case 'comment.posted':
    case 'user.mentioned':
      return typeof postId === 'number' ? { name: 'post-detail', params: { id: postId } } : null
    case 'post.shared':
      return typeof sharePostId === 'number' ? { name: 'post-detail', params: { id: sharePostId } } : null
    case 'user.followed':
      return notification.actor.id !== null
        ? { name: 'user-profile', params: { id: notification.actor.id } }
        : null
    default:
      return null
  }
}
