import { defineStore } from 'pinia'
import { ref } from 'vue'
import { songApi } from '../api/songApi'
import type { CreateSongPayload, Song, UpdateSongPayload } from '../types'

export const useSongStore = defineStore('songs', () => {
  const songs = ref<Song[]>([])
  const loading = ref(false)
  const loaded = ref(false)

  async function fetchAdminSongs(): Promise<void> {
    loading.value = true
    try {
      const response = await songApi.listSongs()
      songs.value = response.data ?? []
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  async function createSong(payload: CreateSongPayload): Promise<void> {
    const response = await songApi.createSong(payload)
    if (response.data) {
      songs.value = [...songs.value, response.data]
    }
  }

  async function updateSong(id: number, payload: UpdateSongPayload): Promise<void> {
    const response = await songApi.updateSong(id, payload)
    if (response.data) {
      const index = songs.value.findIndex((song) => song.id === id)
      if (index !== -1) songs.value[index] = response.data
    }
  }

  async function deleteSong(id: number): Promise<void> {
    await songApi.deleteSong(id)
    songs.value = songs.value.filter((song) => song.id !== id)
  }

  return {
    songs,
    loading,
    fetchAdminSongs,
    createSong,
    updateSong,
    deleteSong,
  }
})
