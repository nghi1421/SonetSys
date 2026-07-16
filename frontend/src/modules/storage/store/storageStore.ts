import { defineStore } from 'pinia'
import { ref } from 'vue'
import { storageApi } from '../api/storageApi'
import type {
  Media,
  MediaKind,
  MediaListMeta,
  StorageConfig,
  UpdateStorageConfigPayload,
} from '../types'

export const useStorageStore = defineStore('storage', () => {
  const config = ref<StorageConfig | null>(null)
  const loadingConfig = ref(false)
  const savingConfig = ref(false)

  const media = ref<Media[]>([])
  const mediaMeta = ref<MediaListMeta | null>(null)
  const loadingMedia = ref(false)

  async function fetchSettings(): Promise<void> {
    loadingConfig.value = true
    try {
      const response = await storageApi.getSettings()
      config.value = response.data
    } finally {
      loadingConfig.value = false
    }
  }

  async function saveSettings(payload: UpdateStorageConfigPayload): Promise<void> {
    savingConfig.value = true
    try {
      const response = await storageApi.updateSettings(payload)
      config.value = response.data
    } finally {
      savingConfig.value = false
    }
  }

  async function fetchMedia(page = 1, type?: MediaKind): Promise<void> {
    loadingMedia.value = true
    try {
      const response = await storageApi.listMedia(page, type)
      media.value = response.data
      mediaMeta.value = response.meta
    } finally {
      loadingMedia.value = false
    }
  }

  async function deleteMedia(mediaId: number): Promise<void> {
    await storageApi.deleteMedia(mediaId)
    media.value = media.value.filter((item) => item.id !== mediaId)
  }

  return {
    config,
    loadingConfig,
    savingConfig,
    media,
    mediaMeta,
    loadingMedia,
    fetchSettings,
    saveSettings,
    fetchMedia,
    deleteMedia,
  }
})
