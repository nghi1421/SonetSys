import type { User } from '@/modules/auth/types'

export type PlanInterval = 'free' | 'month' | 'year'

export interface Plan {
  id: number
  name: string
  slug: string
  price_cents: number
  interval: PlanInterval
  max_users: number | null
  features: string[]
  is_active: boolean
  created_at: string
}

export interface CreatePlanPayload {
  name: string
  slug: string
  price_cents: number
  interval: PlanInterval
  max_users?: number | null
  features?: string[]
}

export interface UpdatePlanPayload {
  name: string
  price_cents: number
  interval: PlanInterval
  max_users?: number | null
  features?: string[]
  is_active: boolean
}

export type SubscriptionStatus = 'trialing' | 'active' | 'expired' | 'canceled'

export type TenantStatus = 'trial' | 'active' | 'suspended'

export interface TenantSubscription {
  id: number
  status: SubscriptionStatus
  starts_at: string
  expires_at: string | null
  plan: Plan
  tenant_status: TenantStatus
}

export interface UpdateSubscriptionPayload {
  plan_id: number
}

export interface AdminTenant {
  tenant: {
    id: number
    name: string
    slug: string
    status: TenantStatus
    enabled_modules: string[]
    settings: Record<string, unknown>
    created_at: string
  }
  subscription: TenantSubscription | null
}

export interface RegisterTenantPayload {
  company_name: string
  company_slug: string
  admin_name: string
  admin_email: string
  admin_password: string
  plan_id: number
}

export interface RegisterTenantResult {
  tenant: { id: number; name: string; slug: string; status: string }
  admin: User
  subscription: TenantSubscription
  token: string
}
