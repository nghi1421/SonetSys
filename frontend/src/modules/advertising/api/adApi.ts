import { http } from '@/shared/api/http'
import type { ApiResponse, PaginationMeta } from '@/shared/api/types'
import type { Post } from '@/modules/feed/types'
import type { AdCampaign, AdminAdCampaign } from '../types'

export interface SubmitBoostPayload {
  post_id: number
  budget: number
  days: number
}

export const adApi = {
  async fetchEligiblePosts() {
    const { data } = await http.get<ApiResponse<Post[]>>('/ads/eligible-posts')
    return data.data ?? []
  },

  async fetchCampaigns() {
    const { data } = await http.get<ApiResponse<AdCampaign[]>>('/ads/campaigns')
    return data.data ?? []
  },

  async submitBoost(payload: SubmitBoostPayload) {
    const { data } = await http.post<ApiResponse<AdCampaign>>('/ads/campaigns', payload)
    return data
  },

  async cancelCampaign(id: number) {
    const { data } = await http.delete<ApiResponse<AdCampaign>>(`/ads/campaigns/${id}`)
    return data
  },

  async fetchAdminQueue(page: number) {
    const { data } = await http.get<ApiResponse<AdminAdCampaign[]>>('/admin/ads', {
      params: { page },
    })
    return { data: data.data ?? [], meta: (data.meta as unknown as PaginationMeta) ?? null }
  },

  async approveCampaign(id: number) {
    const { data } = await http.post<ApiResponse<AdminAdCampaign>>(`/admin/ads/${id}/approve`)
    return data
  },

  async rejectCampaign(id: number, reason: string) {
    const { data } = await http.post<ApiResponse<AdminAdCampaign>>(`/admin/ads/${id}/reject`, { reason })
    return data
  },
}
