import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getStoredTheme, setStoredTheme } from '@/shared/api/themeStorage'

export type Theme = 'dark' | 'light'

const DEFAULT_THEME: Theme = 'dark'

function resolveInitialTheme(): Theme {
  const stored = getStoredTheme()
  if (stored === 'dark' || stored === 'light') {
    return stored
  }

  return DEFAULT_THEME
}

export const useThemeStore = defineStore('theme', () => {
  const currentTheme = ref<Theme>(resolveInitialTheme())

  function setTheme(theme: Theme): void {
    currentTheme.value = theme
    setStoredTheme(theme)
    document.documentElement.dataset.theme = theme
  }

  function toggleTheme(): void {
    setTheme(currentTheme.value === 'dark' ? 'light' : 'dark')
  }

  setTheme(currentTheme.value)

  return {
    currentTheme,
    setTheme,
    toggleTheme,
  }
})
