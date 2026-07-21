import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type {
  CreateReactionTypePayload,
  ReactionTypeDef,
  UpdateReactionTypePayload,
} from '../types'

function toRequestBody(payload: CreateReactionTypePayload | UpdateReactionTypePayload): FormData {
  const form = new FormData()
  if ('key' in payload) form.append('key', payload.key)
  form.append('label', payload.label)
  if (payload.emoji) form.append('emoji', payload.emoji)
  if (payload.icon) form.append('icon', payload.icon)
  if (payload.sort_order !== undefined) form.append('sort_order', String(payload.sort_order))
  return form
}

export const reactionTypeApi = {
  async fetchReactionTypes() {
    const { data } = await http.get<ApiResponse<ReactionTypeDef[]>>('/reaction-types')
    return data
  },

  async fetchAdminReactionTypes() {
    const { data } = await http.get<ApiResponse<ReactionTypeDef[]>>('/admin/reaction-types')
    return data
  },

  async createReactionType(payload: CreateReactionTypePayload) {
    const { data } = await http.post<ApiResponse<ReactionTypeDef>>(
      '/admin/reaction-types',
      toRequestBody(payload),
    )
    return data
  },

  async updateReactionType(id: number, payload: UpdateReactionTypePayload) {
    const form = toRequestBody(payload)
    form.append('_method', 'PUT')
    const { data } = await http.post<ApiResponse<ReactionTypeDef>>(
      `/admin/reaction-types/${id}`,
      form,
    )
    return data
  },

  async deleteReactionType(id: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/admin/reaction-types/${id}`)
    return data
  },
}
