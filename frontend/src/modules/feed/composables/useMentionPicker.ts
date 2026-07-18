import { ref } from 'vue'
import { mentionApi } from '../api/mentionApi'
import type { MentionCandidate } from '../types'

// Mirrors the check-in location picker's exact debounce/min-length convention.
const MENTION_SEARCH_DEBOUNCE_MS = 400
const MENTION_MIN_QUERY_LENGTH = 2

export function useMentionPicker() {
  const showPicker = ref(false)
  const query = ref('')
  const results = ref<MentionCandidate[]>([])
  const searching = ref(false)
  let debounceTimer: ReturnType<typeof setTimeout> | undefined

  function toggle(): void {
    showPicker.value = !showPicker.value
  }

  function close(): void {
    showPicker.value = false
  }

  function onSearchInput(): void {
    if (debounceTimer) clearTimeout(debounceTimer)

    const trimmed = query.value.trim()
    if (trimmed.length < MENTION_MIN_QUERY_LENGTH) {
      results.value = []
      searching.value = false
      return
    }

    debounceTimer = setTimeout(async () => {
      searching.value = true
      try {
        const response = await mentionApi.search(trimmed)
        results.value = response.data ?? []
      } catch {
        results.value = []
      } finally {
        searching.value = false
      }
    }, MENTION_SEARCH_DEBOUNCE_MS)
  }

  function reset(): void {
    query.value = ''
    results.value = []
    showPicker.value = false
  }

  function dispose(): void {
    if (debounceTimer) clearTimeout(debounceTimer)
  }

  return { showPicker, query, results, searching, toggle, close, onSearchInput, reset, dispose }
}
