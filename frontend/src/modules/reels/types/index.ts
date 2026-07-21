import type { Post } from '@/modules/feed/types'

export type Reel = Post

export interface CreateReelPayload {
  media: File
  body?: string
  mentioned_user_ids?: number[]
}
