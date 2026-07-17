export type GroupVisibility = 'public' | 'private'

export type GroupMemberRole = 'owner' | 'admin' | 'member'

export type GroupMemberStatus = 'pending' | 'approved'

export interface GroupOwner {
  id: number | null
  name: string | null
}

export interface GroupViewerMembership {
  role: GroupMemberRole
  status: GroupMemberStatus
}

export interface Group {
  id: number
  name: string
  slug: string
  description: string | null
  visibility: GroupVisibility
  avatar_url: string | null
  cover_url: string | null
  members_count: number
  owner: GroupOwner
  viewer_membership: GroupViewerMembership | null
  created_at: string
}

export interface GroupMemberUser {
  id: number | null
  name: string | null
  avatar_url: string | null
}

export interface GroupMember {
  id: number
  role: GroupMemberRole
  status: GroupMemberStatus
  joined_at: string | null
  user: GroupMemberUser
}

export interface CreateGroupPayload {
  name: string
  description?: string
  visibility?: GroupVisibility
}

export interface UpdateGroupPayload {
  name: string
  description?: string
  visibility: GroupVisibility
}
