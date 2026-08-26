<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, Music, Pause, Play } from '@lucide/vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
import { songApi } from '../api/songApi'
import type { Song } from '../types'

const props = defineProps<{ open: boolean }>()

const emit = defineEmits<{
  close: []
  confirm: [payload: { song: Song; startSec: number }]
}>()

const { t } = useI18n()

const DEBOUNCE_MS = 300
const DEFAULT_DURATION_SEC = 30

const step = ref<'search' | 'trim'>('search')
const query = ref('')
const results = ref<Song[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const selectedSong = ref<Song | null>(null)
const startSec = ref(0)
const playingId = ref<number | null>(null)
const previewRef = ref<HTMLAudioElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | undefined

const durationSec = computed(() => selectedSong.value?.duration_sec ?? DEFAULT_DURATION_SEC)

async function runSearch(): Promise<void> {
  loading.value = true
  error.value = null
  try {
    const response = await songApi.searchSongs(query.value.trim() || undefined)
    results.value = response.data ?? []
  } catch {
    error.value = t('songs.picker.searchError')
  } finally {
    loading.value = false
  }
}

function onQueryInput(): void {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(runSearch, DEBOUNCE_MS)
}

function stopPreview(): void {
  previewRef.value?.pause()
  playingId.value = null
}

function togglePreview(song: Song): void {
  const audio = previewRef.value
  if (!audio) return

  if (playingId.value === song.id) {
    stopPreview()
    return
  }

  audio.src = song.audio_url
  audio.currentTime = 0
  audio.play().catch(() => {})
  playingId.value = song.id
}

function selectSong(song: Song): void {
  stopPreview()
  selectedSong.value = song
  startSec.value = 0
  step.value = 'trim'

  const audio = previewRef.value
  if (!audio) return
  audio.src = song.audio_url
  audio.currentTime = 0
  audio.play().catch(() => {})
  playingId.value = song.id
}

function onSeek(): void {
  const audio = previewRef.value
  if (!audio) return
  audio.currentTime = startSec.value
  if (playingId.value !== selectedSong.value?.id) {
    audio.play().catch(() => {})
    playingId.value = selectedSong.value?.id ?? null
  }
}

function backToSearch(): void {
  stopPreview()
  selectedSong.value = null
  step.value = 'search'
}

function confirm(): void {
  if (!selectedSong.value) return
  stopPreview()
  emit('confirm', { song: selectedSong.value, startSec: Math.floor(startSec.value) })
}

function close(): void {
  stopPreview()
  emit('close')
}

function formatTime(seconds: number): string {
  const total = Math.max(0, Math.floor(seconds))
  const minutes = Math.floor(total / 60)
  const secs = total % 60
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return
    step.value = 'search'
    query.value = ''
    selectedSong.value = null
    startSec.value = 0
    runSearch()
  },
)

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
  stopPreview()
})
</script>

<template>
  <AppModal :open="open" size="md" @close="close">
    <audio ref="previewRef" class="hidden" @ended="playingId = null" />

    <div v-if="step === 'search'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-bold text-cyber-text">{{ t('songs.picker.title') }}</h2>
        <button
          type="button"
          class="text-xs text-cyber-muted transition-colors duration-300 hover:text-cyber-neon-cyan"
          @click="close"
        >
          {{ t('common.close') }}
        </button>
      </div>

      <AppInput
        v-model="query"
        :label="t('songs.picker.searchLabel')"
        type="search"
        @input="onQueryInput"
      />

      <p v-if="error" class="rounded-hud border border-cyber-neon-pink/40 bg-cyber-neon-pink/10 px-3 py-2 text-xs text-cyber-neon-pink">
        {{ error }}
      </p>

      <div v-else-if="loading" class="space-y-2">
        <div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded-hud bg-cyber-glass" />
      </div>

      <div
        v-else-if="results.length === 0"
        class="flex flex-col items-center gap-2 py-8 text-center text-cyber-muted"
      >
        <Music class="h-6 w-6" />
        <p class="text-xs">{{ t('songs.picker.empty') }}</p>
      </div>

      <ul v-else class="max-h-80 space-y-1 overflow-y-auto">
        <li
          v-for="song in results"
          :key="song.id"
          class="flex items-center gap-3 rounded-hud border border-transparent px-2 py-2 transition-colors duration-300 hover:border-cyber-border hover:bg-cyber-glass"
        >
          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyber-neon-indigo/20 text-cyber-neon-indigo transition-colors duration-300 hover:bg-cyber-neon-indigo/30"
            :aria-label="playingId === song.id ? t('songs.picker.pause') : t('songs.picker.preview')"
            @click="togglePreview(song)"
          >
            <Pause v-if="playingId === song.id" class="h-3.5 w-3.5" />
            <Play v-else class="h-3.5 w-3.5" />
          </button>

          <button type="button" class="min-w-0 flex-1 text-left" @click="selectSong(song)">
            <p class="truncate text-xs font-semibold text-cyber-text">{{ song.title }}</p>
            <p v-if="song.artist" class="truncate text-xs text-cyber-muted">{{ song.artist }}</p>
          </button>
        </li>
      </ul>
    </div>

    <div v-else-if="step === 'trim' && selectedSong" class="space-y-4">
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-full p-1 text-cyber-muted transition-colors duration-300 hover:text-cyber-neon-cyan"
          :aria-label="t('common.back')"
          @click="backToSearch"
        >
          <ChevronLeft class="h-4 w-4" />
        </button>
        <h2 class="text-sm font-bold text-cyber-text">{{ t('songs.picker.trimTitle') }}</h2>
      </div>

      <div class="rounded-hud border border-cyber-border bg-cyber-glass p-3">
        <p class="truncate text-xs font-semibold text-cyber-text">{{ selectedSong.title }}</p>
        <p v-if="selectedSong.artist" class="truncate text-xs text-cyber-muted">{{ selectedSong.artist }}</p>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between text-xs text-cyber-muted">
          <span>{{ t('songs.picker.startsAt') }}</span>
          <span class="font-mono tabular-nums">{{ formatTime(startSec) }} / {{ formatTime(durationSec) }}</span>
        </div>
        <input
          v-model.number="startSec"
          type="range"
          min="0"
          :max="Math.max(durationSec - 1, 0)"
          step="1"
          class="w-full accent-cyber-neon-indigo"
          @input="onSeek"
        />
      </div>

      <button
        type="button"
        class="w-full rounded-hud bg-cyber-neon-indigo px-4 py-2 text-xs font-bold text-white transition-all duration-300 hover:shadow-cyan-glow"
        @click="confirm"
      >
        {{ t('songs.picker.confirm') }}
      </button>
    </div>
  </AppModal>
</template>
