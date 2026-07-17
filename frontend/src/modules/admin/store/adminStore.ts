import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminApi } from '../api/adminApi'
import type { AdminStats } from '../types'

export const useAdminStore = defineStore('admin', () => {
  const stats = ref<AdminStats | null>(null)
  const loadingStats = ref(false)

  async function fetchStats(): Promise<void> {
    loadingStats.value = true
    try {
      const response = await adminApi.getStats()
      stats.value = response.data
    } finally {
      loadingStats.value = false
    }
  }

  return {
    stats,
    loadingStats,
    fetchStats,
  }
})
