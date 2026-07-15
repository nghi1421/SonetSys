import { defineStore } from 'pinia'
import { ref } from 'vue'
import { staticPageApi } from '../api/staticPageApi'
import type { CreateStaticPagePayload, StaticPage, UpdateStaticPagePayload } from '../types'

export const useStaticPageStore = defineStore('staticPages', () => {
  const pages = ref<StaticPage[]>([])
  const loading = ref(false)

  async function fetchPages(): Promise<void> {
    loading.value = true
    try {
      const response = await staticPageApi.list()
      pages.value = response.data ?? []
    } finally {
      loading.value = false
    }
  }

  async function createPage(payload: CreateStaticPagePayload): Promise<void> {
    const response = await staticPageApi.create(payload)
    if (response.data) {
      pages.value = [response.data, ...pages.value]
    }
  }

  async function updatePage(id: number, payload: UpdateStaticPagePayload): Promise<void> {
    const response = await staticPageApi.update(id, payload)
    if (response.data) {
      const index = pages.value.findIndex((page) => page.id === id)
      if (index !== -1) pages.value[index] = response.data
    }
  }

  async function removePage(id: number): Promise<void> {
    await staticPageApi.remove(id)
    pages.value = pages.value.filter((page) => page.id !== id)
  }

  return { pages, loading, fetchPages, createPage, updatePage, removePage }
})
