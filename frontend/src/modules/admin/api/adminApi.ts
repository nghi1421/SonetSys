import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { AdminStats } from '../types'

export const adminApi = {
  async getStats() {
    const { data } = await http.get<ApiResponse<AdminStats>>('/admin/stats')
    return data
  },
}
