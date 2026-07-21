import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { SystemSettings, UpdateSystemSettingsPayload } from '../types'

export const settingsApi = {
  async getSettings() {
    const { data } = await http.get<ApiResponse<SystemSettings>>('/settings')
    return data
  },

  async updateSettings(payload: UpdateSystemSettingsPayload) {
    const { data } = await http.put<ApiResponse<SystemSettings>>('/settings', payload)
    return data
  },
}
