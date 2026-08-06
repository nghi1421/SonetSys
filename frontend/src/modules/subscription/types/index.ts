export type SubscriptionPlanId = 'basic' | 'pro'
export type SubscriptionStatus = 'active' | 'expired'

export interface SubscriptionPlan {
  id: SubscriptionPlanId
  label: string
  price: number
  boost_fee_waiver_percent: number
}

export interface UserSubscription {
  id: number
  plan: SubscriptionPlanId
  status: SubscriptionStatus
  price: number
  auto_renew: boolean
  current_period_start: string
  current_period_end: string
  cancelled_at: string | null
  ended_at: string | null
  created_at: string
}

export interface AdminUserSubscription extends UserSubscription {
  user: {
    id: number | null
    name: string | null
    email: string | null
  }
}
