import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { MentionCandidate } from '../types'

export const mentionApi = {
  async search(query: string) {
    const { data } = await http.get<ApiResponse<MentionCandidate[]>>('/users/search', {
      params: { q: query },
    })
    return data
  },
}
