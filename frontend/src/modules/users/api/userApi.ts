import { http } from '@/shared/api/http'
import type { ApiResponse, PaginationMeta } from '@/shared/api/types'
import type { User } from '@/modules/auth/types'

export interface UpdateUserPayload {
  role: string
  status: string
}

export const userApi = {
  async list(page: number) {
    const { data } = await http.get<ApiResponse<User[]>>('/users', {
      params: { page },
    })
    return { data: data.data ?? [], meta: (data.meta as unknown as PaginationMeta) ?? null }
  },

  async update(userId: number, payload: UpdateUserPayload) {
    const { data } = await http.put<ApiResponse<User>>(`/users/${userId}`, payload)
    return data
  },
}
