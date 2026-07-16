import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type {
  Media,
  MediaKind,
  MediaListMeta,
  StorageConfig,
  UpdateStorageConfigPayload,
} from '../types'

export const storageApi = {
  async getSettings() {
    const { data } = await http.get<ApiResponse<StorageConfig>>('/storage/settings')
    return data
  },

  async updateSettings(payload: UpdateStorageConfigPayload) {
    const { data } = await http.put<ApiResponse<StorageConfig>>('/storage/settings', payload)
    return data
  },

  async listMedia(page: number, type?: MediaKind) {
    const { data } = await http.get<ApiResponse<Media[]>>('/media', {
      params: { page, type },
    })
    return { data: data.data ?? [], meta: (data.meta as unknown as MediaListMeta) ?? null }
  },

  async deleteMedia(mediaId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/media/${mediaId}`)
    return data
  },
}
