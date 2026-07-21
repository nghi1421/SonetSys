import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { AuthResponseData, LoginPayload, RegisterPayload, User } from '../types'

export const authApi = {
  async login(payload: LoginPayload) {
    const { data } = await http.post<ApiResponse<AuthResponseData>>('/auth/login', payload)
    return data
  },

  async register(payload: RegisterPayload) {
    const { data } = await http.post<ApiResponse<AuthResponseData>>('/auth/register', payload)
    return data
  },

  async logout() {
    const { data } = await http.post<ApiResponse<null>>('/auth/logout')
    return data
  },

  async me() {
    const { data } = await http.get<ApiResponse<User>>('/auth/me')
    return data
  },

  async forgotPassword(payload: { email: string }) {
    const { data } = await http.post<ApiResponse<null>>('/auth/forgot-password', payload)
    return data
  },

  async resetPassword(payload: { token: string; email: string; password: string }) {
    const { data } = await http.post<ApiResponse<null>>('/auth/reset-password', payload)
    return data
  },

  async updateProfile(formData: FormData) {
    const { data } = await http.post<ApiResponse<User>>('/profile', formData)
    return data
  },
}
