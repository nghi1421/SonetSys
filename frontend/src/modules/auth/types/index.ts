export interface Role {
  id: number
  slug: string
  name: string
}

export interface User {
  id: number
  name: string
  email: string
  status: 'active' | 'suspended' | 'banned'
  role: Role
  avatar_url: string | null
  cover_url: string | null
  is_premium: boolean
  last_login_at: string | null
  created_at: string
}

export interface UpdateProfilePayload {
  avatar?: File
  cover?: File
  removeAvatar?: boolean
  removeCover?: boolean
}

export interface LoginPayload {
  email: string
  password: string
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
}

export interface AuthResponseData {
  user: User
  token: string
}
