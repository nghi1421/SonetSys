<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { Image, Smile, Video, X } from '@lucide/vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useStickerStore } from '../store/stickerStore'
import type { CreatePostPayload } from '../types'

const props = defineProps<{ onSubmit: (payload: CreatePostPayload) => Promise<void> }>()

const stickerStore = useStickerStore()

const body = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

const mediaFile = ref<File | null>(null)
const mediaType = ref<'image' | 'video' | null>(null)
const mediaPreviewUrl = ref<string | null>(null)
const selectedStickerKey = ref<string | null>(null)
const showStickerPicker = ref(false)

const photoInput = ref<HTMLInputElement | null>(null)
const videoInput = ref<HTMLInputElement | null>(null)

const selectedStickerEmoji = computed(
  () => stickerStore.stickers.find((sticker) => sticker.key === selectedStickerKey.value)?.emoji ?? null,
)

const canSubmit = computed(() => Boolean(body.value.trim() || mediaFile.value || selectedStickerKey.value))

function clearMedia(): void {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
  mediaFile.value = null
  mediaType.value = null
  mediaPreviewUrl.value = null
  selectedStickerKey.value = null
}

function pickPhoto(): void {
  showStickerPicker.value = false
  photoInput.value?.click()
}

function pickVideo(): void {
  showStickerPicker.value = false
  videoInput.value?.click()
}

function onPhotoChange(event: Event): void {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  clearMedia()
  mediaFile.value = file
  mediaType.value = 'image'
  mediaPreviewUrl.value = URL.createObjectURL(file)
}

function onVideoChange(event: Event): void {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  clearMedia()
  mediaFile.value = file
  mediaType.value = 'video'
  mediaPreviewUrl.value = URL.createObjectURL(file)
}

async function toggleStickerPicker(): Promise<void> {
  showStickerPicker.value = !showStickerPicker.value
  if (showStickerPicker.value) {
    await stickerStore.fetchStickers()
  }
}

function selectSticker(key: string): void {
  clearMedia()
  selectedStickerKey.value = key
  showStickerPicker.value = false
}

onBeforeUnmount(() => {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
})

async function handleSubmit(): Promise<void> {
  if (!canSubmit.value) return

  loading.value = true
  error.value = null

  try {
    await props.onSubmit({
      body: body.value.trim(),
      media: mediaFile.value ?? undefined,
      media_type: mediaType.value ?? undefined,
      sticker_key: selectedStickerKey.value ?? undefined,
    })
    body.value = ''
    clearMedia()
  } catch {
    error.value = 'Could not publish your post. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form
    class="space-y-3 rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/30"
    @submit.prevent="handleSubmit"
  >
    <textarea
      v-model="body"
      rows="3"
      placeholder="Share something with your community…"
      class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
    />

    <div v-if="mediaPreviewUrl" class="relative w-fit">
      <img
        v-if="mediaType === 'image'"
        :src="mediaPreviewUrl"
        alt="Selected photo preview"
        class="max-h-64 rounded-hud border border-cyber-border"
      />
      <video
        v-else-if="mediaType === 'video'"
        :src="mediaPreviewUrl"
        controls
        class="max-h-64 rounded-hud border border-cyber-border"
      />
      <button
        type="button"
        class="absolute -right-2 -top-2 rounded-full border border-cyber-border bg-cyber-glass p-1 text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
        aria-label="Remove media"
        @click="clearMedia"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <div v-else-if="selectedStickerEmoji" class="relative w-fit">
      <div
        class="flex h-24 w-24 items-center justify-center rounded-hud border border-cyber-border bg-cyber-surface/60 text-5xl backdrop-blur-md"
      >
        {{ selectedStickerEmoji }}
      </div>
      <button
        type="button"
        class="absolute -right-2 -top-2 rounded-full border border-cyber-border bg-cyber-glass p-1 text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
        aria-label="Remove sticker"
        @click="clearMedia"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <p v-if="error" class="font-mono text-xs text-cyber-neon-pink">{{ error }}</p>

    <div class="flex items-center justify-between">
      <div class="flex items-center gap-1">
        <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhotoChange" />
        <input ref="videoInput" type="file" accept="video/*" class="hidden" @change="onVideoChange" />

        <button
          type="button"
          class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
          aria-label="Add photo"
          @click="pickPhoto"
        >
          <Image class="h-5 w-5" />
        </button>
        <button
          type="button"
          class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
          aria-label="Add video"
          @click="pickVideo"
        >
          <Video class="h-5 w-5" />
        </button>

        <div class="relative">
          <button
            type="button"
            class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
            aria-label="Add sticker"
            @click="toggleStickerPicker"
          >
            <Smile class="h-5 w-5" />
          </button>

          <div v-if="showStickerPicker" class="fixed inset-0 z-0" @click="showStickerPicker = false" />

          <div
            v-if="showStickerPicker"
            class="absolute left-0 z-10 mt-2 grid w-56 grid-cols-4 gap-1 rounded-hud border border-cyber-border bg-cyber-glass p-2 backdrop-blur-md"
            @click.stop
          >
            <button
              v-for="sticker in stickerStore.stickers"
              :key="sticker.key"
              type="button"
              class="flex h-10 w-10 items-center justify-center rounded-hud text-2xl transition-all duration-300 hover:bg-cyber-surface/60"
              @click="selectSticker(sticker.key)"
            >
              {{ sticker.emoji }}
            </button>
          </div>
        </div>
      </div>

      <AppButton type="submit" label="Post" :loading="loading" :disabled="!canSubmit" />
    </div>
  </form>
</template>
