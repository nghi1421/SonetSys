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
}
