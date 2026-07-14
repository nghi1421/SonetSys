export interface Role {
  id: number
  slug: string
  name: string
}

export interface User {
  id: number
  tenant_id: number | null
  name: string
  email: string
  status: 'active' | 'suspended' | 'banned'
  role: Role
  last_login_at: string | null
  created_at: string
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
