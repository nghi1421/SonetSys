const STORAGE_KEY = 'sonetsys_theme'

export function getStoredTheme(): string | null {
  return localStorage.getItem(STORAGE_KEY)
}

export function setStoredTheme(theme: string): void {
  localStorage.setItem(STORAGE_KEY, theme)
}
