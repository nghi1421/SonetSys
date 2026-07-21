import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { registerUnauthorizedHandler } from '@/shared/api/http'
import { clearStoredToken, getStoredToken, setStoredToken } from '@/shared/api/tokenStorage'
import { connectEcho, disconnectEcho } from '@/shared/echo'
import { authApi } from '../api/authApi'
import type { LoginPayload, RegisterPayload, UpdateProfilePayload, User } from '../types'

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

  async function updateProfile(payload: UpdateProfilePayload) {
    const form = new FormData()
    if (payload.avatar) form.append('avatar', payload.avatar)
    if (payload.cover) form.append('cover', payload.cover)
    if (payload.removeAvatar) form.append('remove_avatar', '1')
    if (payload.removeCover) form.append('remove_cover', '1')

    const response = await authApi.updateProfile(form)
    if (response.data) {
      user.value = response.data
    }
    return response
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
    updateProfile,
    setSession,
    clearSession,
  }
})
