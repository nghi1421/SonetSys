import { defineStore } from 'pinia'
import { ref } from 'vue'
import { feedApi } from '@/modules/feed/api/feedApi'
import type { ReactionType } from '@/modules/feed/types'
import { reelApi } from '../api/reelApi'
import type { CreateReelPayload, Reel } from '../types'

export const useReelStore = defineStore('reels', () => {
  const reels = ref<Reel[]>([])
  const nextCursor = ref<string | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)

  async function fetchReels(): Promise<void> {
    loading.value = true
    try {
      const response = await reelApi.fetchReels(null)
      reels.value = response.data ?? []
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      loading.value = false
    }
  }

  async function fetchMore(): Promise<void> {
    if (!nextCursor.value || loadingMore.value) return
    loadingMore.value = true
    try {
      const response = await reelApi.fetchReels(nextCursor.value)
      reels.value = [...reels.value, ...(response.data ?? [])]
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      loadingMore.value = false
    }
  }

  async function createReel(payload: CreateReelPayload): Promise<void> {
    const response = await reelApi.createReel(payload)
    if (response.data) {
      reels.value = [response.data, ...reels.value]
    }
  }

  // Likes/comments hit the same generic /posts/{id}/... endpoints regardless
  // of is_reel — reactToReel mirrors feedStore.reactToPost but mutates
  // reels.value so the badge on a ReelCard stays in sync (a reel never lives
  // in feedStore.posts, so that store's own mutation would silently no-op).
  async function reactToReel(reelId: number, type: ReactionType): Promise<void> {
    const reel = reels.value.find((r) => r.id === reelId)
    if (!reel) return
    const response = await feedApi.togglePostLike(reelId, type)
    if (response.data) {
      reel.my_reaction = response.data.my_reaction
      reel.likes_count = response.data.likes_count
    }
  }

  async function unreactToReel(reelId: number): Promise<void> {
    const reel = reels.value.find((r) => r.id === reelId)
    if (!reel || !reel.my_reaction) return
    const response = await feedApi.togglePostLike(reelId, reel.my_reaction)
    if (response.data) {
      reel.my_reaction = response.data.my_reaction
      reel.likes_count = response.data.likes_count
    }
  }

  async function deleteReel(reelId: number): Promise<void> {
    await feedApi.deletePost(reelId)
    reels.value = reels.value.filter((r) => r.id !== reelId)
  }

  return {
    reels,
    nextCursor,
    loading,
    loadingMore,
    fetchReels,
    fetchMore,
    createReel,
    reactToReel,
    unreactToReel,
    deleteReel,
  }
})
