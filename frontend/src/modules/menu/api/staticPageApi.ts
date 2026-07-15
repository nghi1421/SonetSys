import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { CreateStaticPagePayload, StaticPage, UpdateStaticPagePayload } from '../types'

export const staticPageApi = {
  async list() {
    const { data } = await http.get<ApiResponse<StaticPage[]>>('/pages')
    return data
  },

  async create(payload: CreateStaticPagePayload) {
    const { data } = await http.post<ApiResponse<StaticPage>>('/pages', payload)
    return data
  },

  async update(id: number, payload: UpdateStaticPagePayload) {
    const { data } = await http.put<ApiResponse<StaticPage>>(`/pages/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/pages/${id}`)
    return data
  },
}
