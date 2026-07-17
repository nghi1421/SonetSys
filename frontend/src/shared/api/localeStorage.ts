const STORAGE_KEY = 'sonetsys_locale'

export function getStoredLocale(): string | null {
  return localStorage.getItem(STORAGE_KEY)
}

export function setStoredLocale(locale: string): void {
  localStorage.setItem(STORAGE_KEY, locale)
}
