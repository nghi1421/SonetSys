export type WalletTransactionType = 'credit' | 'debit'

export type WalletTransactionReason = 'admin_topup' | 'ad_spend' | 'ad_refund'

export interface WalletTransaction {
  id: number
  type: WalletTransactionType
  amount: number
  balance_after: number
  reason: WalletTransactionReason
  description: string | null
  created_at: string
}

export interface AdminWallet {
  user_id: number
  user: {
    name: string
    email: string
  }
  balance: number
}
