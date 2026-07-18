import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type {
  Comment,
  CreateCommentPayload,
  CreatePostPayload,
  Post,
  ReactionResult,
  ReactionType,
  UpdatePostPayload,
} from '../types'

function toRequestBody(payload: CreatePostPayload): FormData | CreatePostPayload {
  if (!payload.media && !payload.sticker_key) return payload

  const form = new FormData()
  form.append('body', payload.body)
  if (payload.visibility) form.append('visibility', payload.visibility)
  if (payload.shared_post_id) form.append('shared_post_id', String(payload.shared_post_id))
  if (payload.media) form.append('media', payload.media)
  if (payload.media_type) form.append('media_type', payload.media_type)
  if (payload.sticker_key) form.append('sticker_key', payload.sticker_key)
  if (payload.location_name) form.append('location_name', payload.location_name)
  if (payload.location_lat !== undefined) form.append('location_lat', String(payload.location_lat))
  if (payload.location_lng !== undefined) form.append('location_lng', String(payload.location_lng))
  return form
}

export const feedApi = {
  async fetchFeed(cursor: string | null) {
    const { data } = await http.get<ApiResponse<Post[]>>('/posts', {
      params: cursor ? { cursor } : {},
    })
    return data
  },

  async createPost(payload: CreatePostPayload) {
    const { data } = await http.post<ApiResponse<Post>>('/posts', toRequestBody(payload))
    return data
  },

  async fetchPost(postId: number) {
    const { data } = await http.get<ApiResponse<Post>>(`/posts/${postId}`)
    return data
  },

  async updatePost(postId: number, payload: UpdatePostPayload) {
    const { data } = await http.put<ApiResponse<Post>>(`/posts/${postId}`, payload)
    return data
  },

  async deletePost(postId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/posts/${postId}`)
    return data
  },

  async togglePostLike(postId: number, type?: ReactionType) {
    const { data } = await http.post<ApiResponse<ReactionResult>>(`/posts/${postId}/like`, type ? { type } : undefined)
    return data
  },

  async fetchComments(postId: number) {
    const { data } = await http.get<ApiResponse<Comment[]>>(`/posts/${postId}/comments`)
    return data
  },

  async createComment(postId: number, payload: CreateCommentPayload) {
    const { data } = await http.post<ApiResponse<Comment>>(`/posts/${postId}/comments`, payload)
    return data
  },

  async deleteComment(commentId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/comments/${commentId}`)
    return data
  },

  async toggleCommentLike(commentId: number, type?: ReactionType) {
    const { data } = await http.post<ApiResponse<ReactionResult>>(`/comments/${commentId}/like`, type ? { type } : undefined)
    return data
  },

  async fetchFollowingFeed(cursor: string | null) {
    const { data } = await http.get<ApiResponse<Post[]>>('/posts/following', {
      params: cursor ? { cursor } : {},
    })
    return data
  },

  async fetchGroupFeed(groupId: number, cursor: string | null) {
    const { data } = await http.get<ApiResponse<Post[]>>(`/groups/${groupId}/posts`, {
      params: cursor ? { cursor } : {},
    })
    return data
  },

  async createGroupPost(groupId: number, payload: CreatePostPayload) {
    const { data } = await http.post<ApiResponse<Post>>(
      `/groups/${groupId}/posts`,
      toRequestBody(payload),
    )
    return data
  },
}
