import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type {
  Comment,
  CreateCommentPayload,
  CreatePostPayload,
  Post,
  ToggleLikeResult,
  UpdatePostPayload,
} from '../types'

export const feedApi = {
  async fetchFeed(cursor: string | null) {
    const { data } = await http.get<ApiResponse<Post[]>>('/posts', {
      params: cursor ? { cursor } : {},
    })
    return data
  },

  async createPost(payload: CreatePostPayload) {
    if (payload.media || payload.sticker_key) {
      const form = new FormData()
      form.append('body', payload.body)
      if (payload.visibility) form.append('visibility', payload.visibility)
      if (payload.shared_post_id) form.append('shared_post_id', String(payload.shared_post_id))
      if (payload.media) form.append('media', payload.media)
      if (payload.media_type) form.append('media_type', payload.media_type)
      if (payload.sticker_key) form.append('sticker_key', payload.sticker_key)

      const { data } = await http.post<ApiResponse<Post>>('/posts', form)
      return data
    }

    const { data } = await http.post<ApiResponse<Post>>('/posts', payload)
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

  async togglePostLike(postId: number) {
    const { data } = await http.post<ApiResponse<ToggleLikeResult>>(`/posts/${postId}/like`)
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

  async toggleCommentLike(commentId: number) {
    const { data } = await http.post<ApiResponse<ToggleLikeResult>>(`/comments/${commentId}/like`)
    return data
  },
}
