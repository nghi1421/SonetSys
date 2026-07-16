export type StorageDriver = 'local' | 's3'

export interface StorageConfig {
  driver: StorageDriver
  bucket: string | null
  region: string | null
  key: string | null
  endpoint: string | null
  use_path_style_endpoint: boolean
  has_secret: boolean
}

export interface UpdateStorageConfigPayload {
  driver: StorageDriver
  bucket?: string
  region?: string
  key?: string
  secret?: string
  endpoint?: string
  use_path_style_endpoint?: boolean
}

export type MediaKind = 'image' | 'video' | 'file'

export interface MediaUploader {
  id: number | null
  name: string | null
}

export interface Media {
  id: number
  type: MediaKind
  url: string
  original_name: string
  mime_type: string
  size: number
  mediable_type: string | null
  mediable_id: number | null
  uploaded_by: MediaUploader
  created_at: string
}

export interface MediaListMeta {
  current_page: number
  last_page: number
  total: number
}
