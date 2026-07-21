import { defineStore } from 'pinia'
import { ref } from 'vue'
import { settingsApi } from '../api/settingsApi'
import type { SystemSettings, UpdateSystemSettingsPayload } from '../types'

export const useSettingsStore = defineStore('settings', () => {
  const settings = ref<SystemSettings | null>(null)
  const loadingSettings = ref(false)
  const savingSettings = ref(false)

  async function fetchSettings(): Promise<void> {
    loadingSettings.value = true
    try {
      const response = await settingsApi.getSettings()
      settings.value = response.data
    } finally {
      loadingSettings.value = false
    }
  }

  async function saveSettings(payload: UpdateSystemSettingsPayload): Promise<void> {
    savingSettings.value = true
    try {
      const response = await settingsApi.updateSettings(payload)
      settings.value = response.data
    } finally {
      savingSettings.value = false
    }
  }

  return {
    settings,
    loadingSettings,
    savingSettings,
    fetchSettings,
    saveSettings,
  }
})
