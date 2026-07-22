export interface User {
  id: number | null
  name: string | null
  avatar_url: string | null
}

export type PostVisibility = 'public' | 'members' | 'private'

export type MediaType = 'image' | 'video' | 'sticker'

export interface Sticker {
  key: string
  emoji: string
}

export interface PostLocation {
  name: string
  lat: number
  lng: number
}

export type ReactionType = string

export interface MentionCandidate {
  id: number
  name: string
  avatar_url: string | null
}

export interface Post {
  id: number
  body: string
  visibility: PostVisibility
  metadata: Record<string, unknown>
  likes_count: number
  comments_count: number
  shares_count: number
  my_reaction: ReactionType | null
  is_sponsored: boolean
  is_reel: boolean
  author: User
  hashtags: string[]
  mentions: User[]
  shared_post: Post | null
  media_type: MediaType | null
  media_url: string | null
  sticker_key: string | null
  location: PostLocation | null
  published_at: string | null
  created_at: string
}

export interface Comment {
  id: number
  post_id: number
  parent_id: number | null
  body: string
  likes_count: number
  my_reaction: ReactionType | null
  author: User
  hashtags: string[]
  mentions: User[]
  created_at: string
}

export interface ReactionResult {
  my_reaction: ReactionType | null
  likes_count: number
}

export interface CreatePostPayload {
  body: string
  visibility?: PostVisibility
  shared_post_id?: number
  media?: File
  media_type?: 'image' | 'video'
  sticker_key?: string
  location_name?: string
  location_lat?: number
  location_lng?: number
  mentioned_user_ids?: number[]
}

export interface UpdatePostPayload {
  body: string
  visibility?: PostVisibility
}

export interface CreateCommentPayload {
  body: string
  parent_id?: number
  mentioned_user_ids?: number[]
}
