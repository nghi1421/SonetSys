export interface Song {
  id: number
  title: string
  artist: string | null
  audio_url: string
  cover_url: string | null
  duration_sec: number | null
}

export interface CreateSongPayload {
  title: string
  artist?: string
  audio: File
  cover?: File
}

export interface UpdateSongPayload {
  title?: string
  artist?: string
  audio?: File
  cover?: File
}
