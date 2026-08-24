import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { CreateSongPayload, Song, UpdateSongPayload } from '../types'

function toFormData(payload: CreateSongPayload | UpdateSongPayload): FormData {
  const form = new FormData()
  if (payload.title) form.append('title', payload.title)
  if (payload.artist) form.append('artist', payload.artist)
  if (payload.audio) form.append('audio', payload.audio)
  if (payload.cover) form.append('cover', payload.cover)
  return form
}

export const songApi = {
  async searchSongs(query?: string) {
    const { data } = await http.get<ApiResponse<Song[]>>('/songs', {
      params: { q: query || undefined },
    })
    return data
  },

  async listSongs() {
    const { data } = await http.get<ApiResponse<Song[]>>('/admin/songs')
    return data
  },

  async createSong(payload: CreateSongPayload) {
    const { data } = await http.post<ApiResponse<Song>>('/admin/songs', toFormData(payload))
    return data
  },

  async updateSong(songId: number, payload: UpdateSongPayload) {
    const form = toFormData(payload)
    form.append('_method', 'PUT')
    const { data } = await http.post<ApiResponse<Song>>(`/admin/songs/${songId}`, form)
    return data
  },

  async deleteSong(songId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/admin/songs/${songId}`)
    return data
  },
}
