export interface FollowUser {
  id: number
  name: string
  avatar_url: string | null
  is_following: boolean
}

export interface UserProfile {
  id: number
  name: string
  avatar_url: string | null
  cover_url: string | null
  created_at: string
  followers_count: number
  following_count: number
  is_following: boolean
  is_followed_by: boolean
}
