import { defineStore } from 'pinia'
import { ref } from 'vue'
import { reportApi } from '../api/reportApi'
import type { PaginationMeta } from '@/shared/api/types'
import type { AdminReport, ReportableType, ReportReason } from '../types'

export const useReportStore = defineStore('report', () => {
  const adminReports = ref<AdminReport[]>([])
  const adminMeta = ref<PaginationMeta | null>(null)
  const adminLoading = ref(false)

  async function submitReport(
    type: ReportableType,
    id: number,
    reason: ReportReason,
    details?: string,
  ): Promise<void> {
    await reportApi.submitReport(type, id, { reason, details })
  }

  async function fetchAdminQueue(page = 1): Promise<void> {
    adminLoading.value = true
    try {
      const response = await reportApi.fetchAdminQueue(page)
      adminReports.value = response.data
      adminMeta.value = response.meta
    } finally {
      adminLoading.value = false
    }
  }

  async function resolveReport(id: number): Promise<void> {
    await reportApi.resolveReport(id)
    adminReports.value = adminReports.value.filter((report) => report.id !== id)
  }

  async function dismissReport(id: number): Promise<void> {
    await reportApi.dismissReport(id)
    adminReports.value = adminReports.value.filter((report) => report.id !== id)
  }

  return {
    adminReports,
    adminMeta,
    adminLoading,
    submitReport,
    fetchAdminQueue,
    resolveReport,
    dismissReport,
  }
})
