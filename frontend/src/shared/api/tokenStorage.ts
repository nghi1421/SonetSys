const STORAGE_KEY = 'sonetsys_token'

export function getStoredToken(): string | null {
  return localStorage.getItem(STORAGE_KEY)
}

export function setStoredToken(token: string): void {
  localStorage.setItem(STORAGE_KEY, token)
}

export function clearStoredToken(): void {
  localStorage.removeItem(STORAGE_KEY)
}
