import { defineStore } from 'pinia'
import { ref } from 'vue'
import { userApi } from '../api/userApi'
import type { UpdateUserPayload } from '../api/userApi'
import type { PaginationMeta } from '@/shared/api/types'
import type { User } from '@/modules/auth/types'
import type { RecentUser } from '../types'

export const useUserStore = defineStore('users', () => {
  const users = ref<User[]>([])
  const meta = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  const recentUsers = ref<RecentUser[]>([])
  const recentLoading = ref(false)
  const recentError = ref(false)

  async function fetchUsers(page = 1): Promise<void> {
    loading.value = true
    try {
      const response = await userApi.list(page)
      users.value = response.data
      meta.value = response.meta
    } finally {
      loading.value = false
    }
  }

  async function updateUser(userId: number, payload: UpdateUserPayload): Promise<void> {
    const response = await userApi.update(userId, payload)
    if (response.data) {
      const index = users.value.findIndex((user) => user.id === userId)
      if (index !== -1) users.value[index] = response.data
    }
  }

  async function fetchRecent(): Promise<void> {
    recentLoading.value = true
    recentError.value = false
    try {
      recentUsers.value = await userApi.recent()
    } catch {
      recentError.value = true
    } finally {
      recentLoading.value = false
    }
  }

  return {
    users,
    meta,
    loading,
    fetchUsers,
    updateUser,
    recentUsers,
    recentLoading,
    recentError,
    fetchRecent,
  }
})
