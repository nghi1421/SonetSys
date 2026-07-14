export interface PostAuthor {
  id: number | null
  name: string | null
}

export type PostVisibility = 'public' | 'tenant_only' | 'private'

export type MediaType = 'image' | 'video' | 'sticker'

export interface Sticker {
  key: string
  emoji: string
}

export interface Post {
  id: number
  tenant_id: number
  body: string
  visibility: PostVisibility
  metadata: Record<string, unknown>
  likes_count: number
  comments_count: number
  shares_count: number
  liked_by_me: boolean
  author: PostAuthor
  shared_post: Post | null
  media_type: MediaType | null
  media_url: string | null
  sticker_key: string | null
  published_at: string | null
  created_at: string
}

export interface Comment {
  id: number
  post_id: number
  parent_id: number | null
  body: string
  likes_count: number
  liked_by_me: boolean
  author: PostAuthor
  created_at: string
}

export interface ToggleLikeResult {
  liked: boolean
  likes_count: number
}

export interface CreatePostPayload {
  body: string
  visibility?: PostVisibility
  shared_post_id?: number
  media?: File
  media_type?: 'image' | 'video'
  sticker_key?: string
}

export interface UpdatePostPayload {
  body: string
  visibility?: PostVisibility
}

export interface CreateCommentPayload {
  body: string
  parent_id?: number
}
