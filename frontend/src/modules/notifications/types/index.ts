export type NotificationType = 'post.liked' | 'comment.liked' | 'comment.posted' | 'post.shared'

export interface NotificationActor {
  id: number | null
  name: string | null
}

export interface AppNotification {
  id: string
  type: NotificationType
  actor: NotificationActor
  data: Record<string, unknown>
  read_at: string | null
  created_at: string | null
}
