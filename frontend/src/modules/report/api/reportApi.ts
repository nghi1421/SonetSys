import { http } from '@/shared/api/http'
import type { ApiResponse, PaginationMeta } from '@/shared/api/types'
import type { AdminReport, ReportableType, ReportReason, Report } from '../types'

export interface SubmitReportPayload {
  reason: ReportReason
  details?: string
}

export const reportApi = {
  async submitReport(type: ReportableType, id: number, payload: SubmitReportPayload) {
    const path = type === 'post' ? `/posts/${id}/report` : `/comments/${id}/report`
    const { data } = await http.post<ApiResponse<Report>>(path, payload)
    return data
  },

  async fetchAdminQueue(page: number) {
    const { data } = await http.get<ApiResponse<AdminReport[]>>('/admin/reports', {
      params: { page },
    })
    return { data: data.data ?? [], meta: (data.meta as unknown as PaginationMeta) ?? null }
  },

  async resolveReport(id: number) {
    const { data } = await http.post<ApiResponse<AdminReport>>(`/admin/reports/${id}/resolve`)
    return data
  },

  async dismissReport(id: number) {
    const { data } = await http.post<ApiResponse<AdminReport>>(`/admin/reports/${id}/dismiss`)
    return data
  },
}
