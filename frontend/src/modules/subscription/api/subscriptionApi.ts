import { http } from '@/shared/api/http'
import type { ApiResponse, PaginationMeta } from '@/shared/api/types'
import type { AdminUserSubscription, SubscriptionPlan, SubscriptionPlanId, UserSubscription } from '../types'

export const subscriptionApi = {
  async fetchPlans() {
    const { data } = await http.get<ApiResponse<SubscriptionPlan[]>>('/subscriptions/plans')
    return data.data ?? []
  },

  async fetchCurrent() {
    const { data } = await http.get<ApiResponse<UserSubscription | null>>('/subscriptions/current')
    return data.data ?? null
  },

  async subscribe(plan: SubscriptionPlanId) {
    const { data } = await http.post<ApiResponse<UserSubscription>>('/subscriptions', { plan })
    return data
  },

  async cancel() {
    const { data } = await http.delete<ApiResponse<UserSubscription>>('/subscriptions/current')
    return data
  },

  async fetchAdminList(page: number) {
    const { data } = await http.get<ApiResponse<AdminUserSubscription[]>>('/admin/subscriptions', {
      params: { page },
    })
    return { data: data.data ?? [], meta: (data.meta as unknown as PaginationMeta) ?? null }
  },

  async adminCancel(id: number) {
    const { data } = await http.post<ApiResponse<AdminUserSubscription>>(`/admin/subscriptions/${id}/cancel`)
    return data
  },
}
