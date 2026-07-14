import { defineStore } from 'pinia'
import { ref } from 'vue'
import { stickerApi } from '../api/stickerApi'
import type { Sticker } from '../types'

export const useStickerStore = defineStore('stickers', () => {
  const stickers = ref<Sticker[]>([])
  const loaded = ref(false)

  async function fetchStickers(): Promise<void> {
    if (loaded.value) return
    const response = await stickerApi.list()
    stickers.value = response.data ?? []
    loaded.value = true
  }

  return { stickers, fetchStickers }
})
