import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { BlockedUser } from '../types'

export const blockApi = {
  async fetchBlockedUsers() {
    const { data } = await http.get<ApiResponse<BlockedUser[]>>('/blocked-users')
    return data
  },

  async block(userId: number) {
    const { data } = await http.post<ApiResponse<null>>(`/users/${userId}/block`)
    return data
  },

  async unblock(userId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/users/${userId}/block`)
    return data
  },
}
