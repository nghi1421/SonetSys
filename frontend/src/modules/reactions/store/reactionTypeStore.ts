import { defineStore } from 'pinia'
import { ref } from 'vue'
import { reactionTypeApi } from '../api/reactionTypeApi'
import type { CreateReactionTypePayload, ReactionTypeDef, UpdateReactionTypePayload } from '../types'

export const useReactionTypeStore = defineStore('reactionTypes', () => {
  const types = ref<ReactionTypeDef[]>([])
  const loading = ref(false)
  const loaded = ref(false)

  async function fetchTypes(): Promise<void> {
    loading.value = true
    try {
      const response = await reactionTypeApi.fetchReactionTypes()
      types.value = response.data ?? []
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  async function fetchTypesOnce(): Promise<void> {
    if (loaded.value || loading.value) return
    await fetchTypes()
  }

  async function fetchAdminTypes(): Promise<void> {
    loading.value = true
    try {
      const response = await reactionTypeApi.fetchAdminReactionTypes()
      types.value = response.data ?? []
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  async function createType(payload: CreateReactionTypePayload): Promise<void> {
    const response = await reactionTypeApi.createReactionType(payload)
    if (response.data) {
      types.value = [...types.value, response.data]
    }
  }

  async function updateType(id: number, payload: UpdateReactionTypePayload): Promise<void> {
    const response = await reactionTypeApi.updateReactionType(id, payload)
    if (response.data) {
      const index = types.value.findIndex((t) => t.id === id)
      if (index !== -1) types.value[index] = response.data
    }
  }

  async function deleteType(id: number): Promise<void> {
    await reactionTypeApi.deleteReactionType(id)
    types.value = types.value.filter((t) => t.id !== id)
  }

  return {
    types,
    loading,
    fetchTypes,
    fetchTypesOnce,
    fetchAdminTypes,
    createType,
    updateType,
    deleteType,
  }
})
