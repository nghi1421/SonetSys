import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type {
  AdminTenant,
  CreatePlanPayload,
  Plan,
  RegisterTenantPayload,
  RegisterTenantResult,
  TenantSubscription,
  UpdatePlanPayload,
  UpdateSubscriptionPayload,
} from '../types'

export const billingApi = {
  async listPublicPlans() {
    const { data } = await http.get<ApiResponse<Plan[]>>('/plans')
    return data
  },

  async listAllPlans() {
    const { data } = await http.get<ApiResponse<Plan[]>>('/admin/plans')
    return data
  },

  async createPlan(payload: CreatePlanPayload) {
    const { data } = await http.post<ApiResponse<Plan>>('/admin/plans', payload)
    return data
  },

  async updatePlan(planId: number, payload: UpdatePlanPayload) {
    const { data } = await http.put<ApiResponse<Plan>>(`/admin/plans/${planId}`, payload)
    return data
  },

  async deletePlan(planId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/admin/plans/${planId}`)
    return data
  },

  async registerTenant(payload: RegisterTenantPayload) {
    const { data } = await http.post<ApiResponse<RegisterTenantResult>>(
      '/tenant-registrations',
      payload,
    )
    return data
  },

  async currentSubscription() {
    const { data } = await http.get<ApiResponse<TenantSubscription | null>>('/subscription')
    return data
  },

  async updateSubscription(payload: UpdateSubscriptionPayload) {
    const { data } = await http.put<ApiResponse<TenantSubscription>>('/subscription', payload)
    return data
  },

  async listTenants() {
    const { data } = await http.get<ApiResponse<AdminTenant[]>>('/admin/tenants')
    return data
  },

  async suspendTenant(tenantId: number) {
    const { data } = await http.post<ApiResponse<AdminTenant['tenant']>>(
      `/admin/tenants/${tenantId}/suspend`,
    )
    return data
  },

  async reactivateTenant(tenantId: number) {
    const { data } = await http.post<ApiResponse<AdminTenant['tenant']>>(
      `/admin/tenants/${tenantId}/reactivate`,
    )
    return data
  },
}
