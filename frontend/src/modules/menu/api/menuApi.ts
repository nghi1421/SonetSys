import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { CreateMenuItemPayload, MenuItem, UpdateMenuItemPayload } from '../types'

export const menuApi = {
  async list() {
    const { data } = await http.get<ApiResponse<MenuItem[]>>('/menu')
    return data
  },

  async show(slug: string) {
    const { data } = await http.get<ApiResponse<MenuItem>>(`/menu/${slug}`)
    return data
  },

  async create(payload: CreateMenuItemPayload) {
    const { data } = await http.post<ApiResponse<MenuItem>>('/menu', payload)
    return data
  },

  async update(id: number, payload: UpdateMenuItemPayload) {
    const { data } = await http.put<ApiResponse<MenuItem>>(`/menu/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/menu/${id}`)
    return data
  },

  async reorder(order: number[]) {
    const { data } = await http.post<ApiResponse<null>>('/menu/reorder', { order })
    return data
  },
}
