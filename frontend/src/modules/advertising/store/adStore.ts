import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adApi } from '../api/adApi'
import type { PaginationMeta } from '@/shared/api/types'
import type { Post } from '@/modules/feed/types'
import type { AdCampaign, AdminAdCampaign } from '../types'

export const useAdStore = defineStore('advertising', () => {
  const campaigns = ref<AdCampaign[]>([])
  const eligiblePosts = ref<Post[]>([])
  const loading = ref(false)

  const adminCampaigns = ref<AdminAdCampaign[]>([])
  const adminMeta = ref<PaginationMeta | null>(null)
  const adminLoading = ref(false)

  async function fetchCampaigns(): Promise<void> {
    loading.value = true
    try {
      campaigns.value = await adApi.fetchCampaigns()
    } finally {
      loading.value = false
    }
  }

  async function fetchEligiblePosts(): Promise<void> {
    loading.value = true
    try {
      eligiblePosts.value = await adApi.fetchEligiblePosts()
    } finally {
      loading.value = false
    }
  }

  async function submitBoost(postId: number, budget: number, days: number): Promise<void> {
    const response = await adApi.submitBoost({ post_id: postId, budget, days })
    if (response.data) {
      campaigns.value = [response.data, ...campaigns.value]
    }
  }

  async function cancelCampaign(id: number): Promise<void> {
    const response = await adApi.cancelCampaign(id)
    if (!response.data) return

    const index = campaigns.value.findIndex((campaign) => campaign.id === id)
    if (index !== -1) {
      campaigns.value[index] = response.data
    }
  }

  async function fetchAdminQueue(page = 1): Promise<void> {
    adminLoading.value = true
    try {
      const response = await adApi.fetchAdminQueue(page)
      adminCampaigns.value = response.data
      adminMeta.value = response.meta
    } finally {
      adminLoading.value = false
    }
  }

  async function approveCampaign(id: number): Promise<void> {
    await adApi.approveCampaign(id)
    adminCampaigns.value = adminCampaigns.value.filter((campaign) => campaign.id !== id)
  }

  async function rejectCampaign(id: number, reason: string): Promise<void> {
    await adApi.rejectCampaign(id, reason)
    adminCampaigns.value = adminCampaigns.value.filter((campaign) => campaign.id !== id)
  }

  return {
    campaigns,
    eligiblePosts,
    loading,
    adminCampaigns,
    adminMeta,
    adminLoading,
    fetchCampaigns,
    fetchEligiblePosts,
    submitBoost,
    cancelCampaign,
    fetchAdminQueue,
    approveCampaign,
    rejectCampaign,
  }
})
