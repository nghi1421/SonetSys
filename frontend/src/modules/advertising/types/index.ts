import type { Post } from '@/modules/feed/types'

export type AdCampaignStatus = 'pending' | 'approved' | 'rejected' | 'completed' | 'cancelled'

export interface AdCampaign {
  id: number
  post_id: number
  post: Post | null
  status: AdCampaignStatus
  budget: number
  starts_at: string | null
  ends_at: string | null
  rejection_reason: string | null
  created_at: string
}

export interface AdminAdCampaign extends AdCampaign {
  advertiser: {
    id: number | null
    name: string | null
    email: string | null
  }
}
