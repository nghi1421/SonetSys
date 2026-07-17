import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { registerUnauthorizedHandler } from '@/shared/api/http'
import { clearStoredToken, getStoredToken, setStoredToken } from '@/shared/api/tokenStorage'
import { connectEcho, disconnectEcho } from '@/shared/echo'
import { authApi } from '../api/authApi'
import type { LoginPayload, RegisterPayload, User } from '../types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(getStoredToken())
  const isAuthenticated = computed(() => token.value !== null)

  function setSession(sessionUser: User, sessionToken: string): void {
    user.value = sessionUser
    token.value = sessionToken
    setStoredToken(sessionToken)
    connectEcho(sessionUser.id)
  }

  function clearSession(): void {
    user.value = null
    token.value = null
    clearStoredToken()
    disconnectEcho()
  }

  async function login(payload: LoginPayload) {
    const response = await authApi.login(payload)
    if (response.data) {
      setSession(response.data.user, response.data.token)
    }
    return response
  }

  async function register(payload: RegisterPayload) {
    const response = await authApi.register(payload)
    if (response.data) {
      setSession(response.data.user, response.data.token)
    }
    return response
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } finally {
      clearSession()
    }
  }

  async function fetchCurrentUser() {
    const response = await authApi.me()
    if (response.data) {
      user.value = response.data
      connectEcho(response.data.id)
    }
    return response
  }

  async function forgotPassword(payload: { email: string }) {
    return authApi.forgotPassword(payload)
  }

  async function resetPassword(payload: { token: string; email: string; password: string }) {
    return authApi.resetPassword(payload)
  }

  registerUnauthorizedHandler(clearSession)

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    fetchCurrentUser,
    forgotPassword,
    resetPassword,
    setSession,
    clearSession,
  }
})
