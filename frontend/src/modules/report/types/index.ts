export type ReportReason = 'spam' | 'harassment' | 'inappropriate' | 'other'

export type ReportStatus = 'pending' | 'resolved' | 'dismissed'

export type ReportableType = 'post' | 'comment'

export interface Report {
  id: number
  reportable_type: ReportableType
  reportable_id: number
  reason: ReportReason
  details: string | null
  status: ReportStatus
  created_at: string
}

export interface AdminReport extends Report {
  reporter: {
    id: number | null
    name: string | null
  }
  excerpt: string | null
  post_id: number | null
  reviewed_by: number | null
  reviewed_at: string | null
}
