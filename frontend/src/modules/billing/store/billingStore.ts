import { defineStore } from 'pinia'
import { ref } from 'vue'
import { billingApi } from '../api/billingApi'
import type {
  CreatePlanPayload,
  Plan,
  RegisterTenantPayload,
  TenantSubscription,
  UpdatePlanPayload,
} from '../types'

export const useBillingStore = defineStore('billing', () => {
  const publicPlans = ref<Plan[]>([])
  const loadingPublicPlans = ref(false)

  const plans = ref<Plan[]>([])
  const loadingPlans = ref(false)

  const subscription = ref<TenantSubscription | null>(null)
  const loadingSubscription = ref(false)

  const registering = ref(false)

  async function fetchPublicPlans(): Promise<void> {
    loadingPublicPlans.value = true
    try {
      const response = await billingApi.listPublicPlans()
      publicPlans.value = response.data ?? []
    } finally {
      loadingPublicPlans.value = false
    }
  }

  async function fetchAllPlans(): Promise<void> {
    loadingPlans.value = true
    try {
      const response = await billingApi.listAllPlans()
      plans.value = response.data ?? []
    } finally {
      loadingPlans.value = false
    }
  }

  async function createPlan(payload: CreatePlanPayload): Promise<void> {
    const response = await billingApi.createPlan(payload)
    if (response.data) {
      plans.value = [...plans.value, response.data]
    }
  }

  async function updatePlan(planId: number, payload: UpdatePlanPayload): Promise<void> {
    const response = await billingApi.updatePlan(planId, payload)
    if (response.data) {
      plans.value = plans.value.map((plan) => (plan.id === planId ? response.data! : plan))
    }
  }

  async function deletePlan(planId: number): Promise<void> {
    await billingApi.deletePlan(planId)
    plans.value = plans.value.filter((plan) => plan.id !== planId)
  }

  async function registerTenant(payload: RegisterTenantPayload) {
    registering.value = true
    try {
      return await billingApi.registerTenant(payload)
    } finally {
      registering.value = false
    }
  }

  async function fetchSubscription(): Promise<void> {
    loadingSubscription.value = true
    try {
      const response = await billingApi.currentSubscription()
      subscription.value = response.data
    } finally {
      loadingSubscription.value = false
    }
  }

  return {
    publicPlans,
    loadingPublicPlans,
    plans,
    loadingPlans,
    subscription,
    loadingSubscription,
    registering,
    fetchPublicPlans,
    fetchAllPlans,
    createPlan,
    updatePlan,
    deletePlan,
    registerTenant,
    fetchSubscription,
  }
})
