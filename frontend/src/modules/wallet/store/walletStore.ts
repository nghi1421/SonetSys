import { defineStore } from 'pinia'
import { ref } from 'vue'
import { walletApi } from '../api/walletApi'
import type { TopUpWalletPayload } from '../api/walletApi'
import type { PaginationMeta } from '@/shared/api/types'
import type { AdminWallet, WalletTransaction } from '../types'

export const useWalletStore = defineStore('wallet', () => {
  const balance = ref(0)
  const transactions = ref<WalletTransaction[]>([])
  const meta = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  const adminWallets = ref<AdminWallet[]>([])
  const adminMeta = ref<PaginationMeta | null>(null)
  const adminLoading = ref(false)

  async function fetchWallet(): Promise<void> {
    loading.value = true
    try {
      const response = await walletApi.fetchWallet()
      balance.value = response.data?.balance ?? 0
    } finally {
      loading.value = false
    }
  }

  async function fetchTransactions(page = 1): Promise<void> {
    loading.value = true
    try {
      const response = await walletApi.fetchTransactions(page)
      transactions.value = response.data
      meta.value = response.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchAdminWallets(page = 1): Promise<void> {
    adminLoading.value = true
    try {
      const response = await walletApi.fetchAdminWallets(page)
      adminWallets.value = response.data
      adminMeta.value = response.meta
    } finally {
      adminLoading.value = false
    }
  }

  async function topUp(userId: number, payload: TopUpWalletPayload): Promise<void> {
    const response = await walletApi.topUp(userId, payload)
    const newBalance = response.data?.balance
    if (newBalance === undefined) return

    const index = adminWallets.value.findIndex((wallet) => wallet.user_id === userId)
    const existing = adminWallets.value[index]
    if (existing) {
      adminWallets.value[index] = { ...existing, balance: newBalance }
    }
  }

  return {
    balance,
    transactions,
    meta,
    loading,
    adminWallets,
    adminMeta,
    adminLoading,
    fetchWallet,
    fetchTransactions,
    fetchAdminWallets,
    topUp,
  }
})
