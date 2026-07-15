export interface StaticPage {
  id: number
  title: string
  content: string
  created_at: string
}

export interface MenuItem {
  id: number
  label: string
  slug: string
  position: number
  is_home: boolean
  static_page: StaticPage | null
}

export interface CreateMenuItemPayload {
  label: string
  static_page_id?: number | null
}

export interface UpdateMenuItemPayload {
  label: string
  static_page_id?: number | null
}

export interface ReorderMenuItemsPayload {
  order: number[]
}

export interface CreateStaticPagePayload {
  title: string
  content: string
}

export interface UpdateStaticPagePayload {
  title: string
  content: string
}
