export interface ApiResponse<T> {
  success: boolean
  data: T | null
  error: string | null
  meta: Record<string, unknown> | null
}

export interface PaginationMeta {
  page: number
  per_page: number
  total: number
}

export interface CursorMeta {
  next_cursor: string | null
}

export interface ValidationErrorMeta {
  errors: Record<string, string[]>
}
