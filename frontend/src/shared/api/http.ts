import axios from 'axios'
import { clearStoredToken, getStoredToken } from './tokenStorage'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1',
  headers: {
    Accept: 'application/json',
  },
})

http.interceptors.request.use((config) => {
  const token = getStoredToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // MVP simplification: tenant resolved from a fixed env var rather than
  // subdomain routing — see docs/ARCHITECTURE.md's ResolveTenant middleware.
  const tenantSlug = import.meta.env.VITE_TENANT_SLUG as string | undefined
  if (tenantSlug) {
    config.headers['X-Tenant-Slug'] = tenantSlug
  }

  return config
})

let onUnauthorized: (() => void) | null = null

export function registerUnauthorizedHandler(handler: () => void): void {
  onUnauthorized = handler
}

http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearStoredToken()
      onUnauthorized?.()
    }

    return Promise.reject(error)
  },
)
