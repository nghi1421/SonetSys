import { http } from '@/shared/api/http'
import type { ApiResponse, PaginationMeta } from '@/shared/api/types'
import type { AdminWallet, WalletTransaction } from '../types'

export interface TopUpWalletPayload {
  amount: number
  note?: string
}

export const walletApi = {
  async fetchWallet() {
    const { data } = await http.get<ApiResponse<{ balance: number }>>('/wallet')
    return data
  },

  async fetchTransactions(page: number) {
    const { data } = await http.get<ApiResponse<WalletTransaction[]>>('/wallet/transactions', {
      params: { page },
    })
    return { data: data.data ?? [], meta: (data.meta as unknown as PaginationMeta) ?? null }
  },

  async fetchAdminWallets(page: number) {
    const { data } = await http.get<ApiResponse<AdminWallet[]>>('/admin/wallets', {
      params: { page },
    })
    return { data: data.data ?? [], meta: (data.meta as unknown as PaginationMeta) ?? null }
  },

  async topUp(userId: number, payload: TopUpWalletPayload) {
    const { data } = await http.post<ApiResponse<{ balance: number }>>(
      `/admin/users/${userId}/wallet/topup`,
      payload,
    )
    return data
  },
}
