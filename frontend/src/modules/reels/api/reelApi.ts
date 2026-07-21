import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { CreateReelPayload, Reel } from '../types'

function toRequestBody(payload: CreateReelPayload): FormData {
  const form = new FormData()
  form.append('media', payload.media)
  if (payload.body) form.append('body', payload.body)
  payload.mentioned_user_ids?.forEach((id) => form.append('mentioned_user_ids[]', String(id)))
  return form
}

export const reelApi = {
  async fetchReels(cursor: string | null) {
    const { data } = await http.get<ApiResponse<Reel[]>>('/reels', {
      params: cursor ? { cursor } : {},
    })
    return data
  },

  async createReel(payload: CreateReelPayload) {
    const { data } = await http.post<ApiResponse<Reel>>('/reels', toRequestBody(payload))
    return data
  },
}
