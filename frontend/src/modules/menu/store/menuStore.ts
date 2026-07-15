import { defineStore } from 'pinia'
import { ref } from 'vue'
import { menuApi } from '../api/menuApi'
import type { CreateMenuItemPayload, MenuItem, UpdateMenuItemPayload } from '../types'

export const useMenuStore = defineStore('menu', () => {
  const items = ref<MenuItem[]>([])
  const loading = ref(false)

  async function fetchMenu(): Promise<void> {
    loading.value = true
    try {
      const response = await menuApi.list()
      items.value = response.data ?? []
    } finally {
      loading.value = false
    }
  }

  async function createItem(payload: CreateMenuItemPayload): Promise<void> {
    const response = await menuApi.create(payload)
    if (response.data) {
      items.value = [...items.value, response.data]
    }
  }

  async function updateItem(id: number, payload: UpdateMenuItemPayload): Promise<void> {
    const response = await menuApi.update(id, payload)
    if (response.data) {
      const index = items.value.findIndex((item) => item.id === id)
      if (index !== -1) items.value[index] = response.data
    }
  }

  async function removeItem(id: number): Promise<void> {
    await menuApi.remove(id)
    items.value = items.value.filter((item) => item.id !== id)
  }

  async function reorder(orderedIds: number[]): Promise<void> {
    const byId = new Map(items.value.map((item) => [item.id, item]))
    const previous = items.value

    items.value = orderedIds
      .map((id, index) => {
        const item = byId.get(id)
        return item ? { ...item, position: index } : null
      })
      .filter((item): item is MenuItem => item !== null)

    try {
      await menuApi.reorder(orderedIds)
    } catch (error) {
      items.value = previous
      throw error
    }
  }

  return { items, loading, fetchMenu, createItem, updateItem, removeItem, reorder }
})
