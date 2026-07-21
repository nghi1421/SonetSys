import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { PostLocation } from '../types'

export const locationApi = {
  async search(query: string) {
    const { data } = await http.get<ApiResponse<PostLocation[]>>('/locations/search', {
      params: { q: query },
    })
    return data
  },
}
