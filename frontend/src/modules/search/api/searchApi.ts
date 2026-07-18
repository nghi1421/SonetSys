import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { SearchResults } from '../types'

export const searchApi = {
  async search(query: string) {
    const { data } = await http.get<ApiResponse<SearchResults>>('/search', {
      params: { q: query },
    })
    return data
  },
}
