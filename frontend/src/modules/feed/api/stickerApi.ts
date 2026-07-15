import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { Sticker } from '../types'

export const stickerApi = {
  async list() {
    const { data } = await http.get<ApiResponse<Sticker[]>>('/stickers')
    return data
  },
}
