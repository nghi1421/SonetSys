import { defineStore } from 'pinia'
import { ref } from 'vue'
import { subscriptionApi } from '../api/subscriptionApi'
import type { PaginationMeta } from '@/shared/api/types'
import type { AdminUserSubscription, SubscriptionPlan, SubscriptionPlanId, UserSubscription } from '../types'

export const useSubscriptionStore = defineStore('subscription', () => {
  const plans = ref<SubscriptionPlan[]>([])
  const current = ref<UserSubscription | null>(null)
  const loading = ref(false)

  const adminSubscriptions = ref<AdminUserSubscription[]>([])
  const adminMeta = ref<PaginationMeta | null>(null)
  const adminLoading = ref(false)

  async function fetchPlans(): Promise<void> {
    plans.value = await subscriptionApi.fetchPlans()
  }

  async function fetchCurrent(): Promise<void> {
    loading.value = true
    try {
      current.value = await subscriptionApi.fetchCurrent()
    } finally {
      loading.value = false
    }
  }

  async function subscribe(plan: SubscriptionPlanId): Promise<void> {
    const response = await subscriptionApi.subscribe(plan)
    if (response.data) {
      current.value = response.data
    }
  }

  async function cancel(): Promise<void> {
    const response = await subscriptionApi.cancel()
    if (response.data) {
      current.value = response.data
    }
  }

  async function fetchAdminList(page = 1): Promise<void> {
    adminLoading.value = true
    try {
      const response = await subscriptionApi.fetchAdminList(page)
      adminSubscriptions.value = response.data
      adminMeta.value = response.meta
    } finally {
      adminLoading.value = false
    }
  }

  async function adminCancel(id: number): Promise<void> {
    const response = await subscriptionApi.adminCancel(id)
    if (!response.data) return

    const index = adminSubscriptions.value.findIndex((subscription) => subscription.id === id)
    if (index !== -1) {
      adminSubscriptions.value[index] = response.data
    }
  }

  return {
    plans,
    current,
    loading,
    adminSubscriptions,
    adminMeta,
    adminLoading,
    fetchPlans,
    fetchCurrent,
    subscribe,
    cancel,
    fetchAdminList,
    adminCancel,
  }
})
