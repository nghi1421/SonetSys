import { defineStore } from 'pinia'
import { ref } from 'vue'
import { DEFAULT_LOCALE, i18n, SUPPORTED_LOCALES } from '@/i18n'
import type { SupportedLocale } from '@/i18n'
import { getStoredLocale, setStoredLocale } from '@/shared/api/localeStorage'

function resolveInitialLocale(): SupportedLocale {
  const stored = getStoredLocale()
  if (stored && SUPPORTED_LOCALES.includes(stored as SupportedLocale)) {
    return stored as SupportedLocale
  }

  return DEFAULT_LOCALE
}

export const useLocaleStore = defineStore('locale', () => {
  const currentLocale = ref<SupportedLocale>(resolveInitialLocale())

  function setLocale(locale: SupportedLocale): void {
    currentLocale.value = locale
    i18n.global.locale.value = locale
    setStoredLocale(locale)
    document.documentElement.lang = locale
  }

  setLocale(currentLocale.value)

  return {
    currentLocale,
    setLocale,
  }
})
