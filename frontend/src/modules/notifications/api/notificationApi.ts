import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { AppNotification } from '../types'

export const notificationApi = {
  async list() {
    const { data } = await http.get<ApiResponse<AppNotification[]>>('/notifications')
    return data
  },

  async markAsRead(notificationId: string) {
    const { data } = await http.post<ApiResponse<null>>(`/notifications/${notificationId}/read`)
    return data
  },

  async markAllAsRead() {
    const { data } = await http.post<ApiResponse<null>>('/notifications/read-all')
    return data
  },
}
