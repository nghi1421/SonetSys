import { defineStore } from 'pinia'
import { ref } from 'vue'
import { searchApi } from '../api/searchApi'
import type { SearchResults } from '../types'

function emptyResults(): SearchResults {
  return { posts: [], users: [], groups: [], hashtags: [] }
}

export const useSearchStore = defineStore('search', () => {
  const results = ref<SearchResults>(emptyResults())
  const loading = ref(false)
  const searched = ref(false)
  const error = ref(false)

  async function search(query: string): Promise<void> {
    loading.value = true
    error.value = false
    try {
      const response = await searchApi.search(query)
      results.value = response.data ?? emptyResults()
    } catch {
      results.value = emptyResults()
      error.value = true
    } finally {
      searched.value = true
      loading.value = false
    }
  }

  function reset(): void {
    results.value = emptyResults()
    searched.value = false
    error.value = false
    loading.value = false
  }

  return { results, loading, searched, error, search, reset }
})
