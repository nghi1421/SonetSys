import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { Post } from '@/modules/feed/types'
import type { FollowUser, UserProfile } from '../types'

export const followApi = {
  async fetchProfile(userId: number) {
    const { data } = await http.get<ApiResponse<UserProfile>>(`/users/${userId}/profile`)
    return data
  },

  async fetchProfilePosts(userId: number, cursor: string | null) {
    const { data } = await http.get<ApiResponse<Post[]>>(`/users/${userId}/posts`, {
      params: cursor ? { cursor } : {},
    })
    return data
  },

  async fetchFollowers(userId: number) {
    const { data } = await http.get<ApiResponse<FollowUser[]>>(`/users/${userId}/followers`)
    return data
  },

  async fetchFollowing(userId: number) {
    const { data } = await http.get<ApiResponse<FollowUser[]>>(`/users/${userId}/following`)
    return data
  },

  async follow(userId: number) {
    const { data } = await http.post<ApiResponse<null>>(`/users/${userId}/follow`)
    return data
  },

  async unfollow(userId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/users/${userId}/follow`)
    return data
  },
}
