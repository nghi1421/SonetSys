<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { Image, Smile, Video, X } from '@lucide/vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useFeedStore } from '../store/feedStore'
import { useStickerStore } from '../store/stickerStore'

const feedStore = useFeedStore()
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

async function onSubmit(): Promise<void> {
  if (!canSubmit.value) return

  loading.value = true
  error.value = null

  try {
    await feedStore.createPost({
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
    class="space-y-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
    @submit.prevent="onSubmit"
  >
    <textarea
      v-model="body"
      rows="3"
      placeholder="Share something with your community…"
      class="block w-full resize-none rounded-md border border-zinc-200 px-3 py-2 text-sm text-slate-900 transition-colors duration-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-accent-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
    />

    <div v-if="mediaPreviewUrl" class="relative w-fit">
      <img
        v-if="mediaType === 'image'"
        :src="mediaPreviewUrl"
        alt="Selected photo preview"
        class="max-h-64 rounded-md border border-zinc-200 dark:border-zinc-700"
      />
      <video
        v-else-if="mediaType === 'video'"
        :src="mediaPreviewUrl"
        controls
        class="max-h-64 rounded-md border border-zinc-200 dark:border-zinc-700"
      />
      <button
        type="button"
        class="absolute -right-2 -top-2 rounded-full bg-zinc-900 p-1 text-white transition-colors duration-200 hover:bg-zinc-700"
        aria-label="Remove media"
        @click="clearMedia"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <div v-else-if="selectedStickerEmoji" class="relative w-fit">
      <div
        class="flex h-24 w-24 items-center justify-center rounded-md border border-zinc-200 text-5xl dark:border-zinc-700"
      >
        {{ selectedStickerEmoji }}
      </div>
      <button
        type="button"
        class="absolute -right-2 -top-2 rounded-full bg-zinc-900 p-1 text-white transition-colors duration-200 hover:bg-zinc-700"
        aria-label="Remove sticker"
        @click="clearMedia"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

    <div class="flex items-center justify-between">
      <div class="flex items-center gap-1">
        <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhotoChange" />
        <input ref="videoInput" type="file" accept="video/*" class="hidden" @change="onVideoChange" />

        <button
          type="button"
          class="rounded-md p-2 text-slate-500 transition-colors duration-200 hover:bg-zinc-100 hover:text-accent-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
          aria-label="Add photo"
          @click="pickPhoto"
        >
          <Image class="h-5 w-5" />
        </button>
        <button
          type="button"
          class="rounded-md p-2 text-slate-500 transition-colors duration-200 hover:bg-zinc-100 hover:text-accent-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
          aria-label="Add video"
          @click="pickVideo"
        >
          <Video class="h-5 w-5" />
        </button>

        <div class="relative">
          <button
            type="button"
            class="rounded-md p-2 text-slate-500 transition-colors duration-200 hover:bg-zinc-100 hover:text-accent-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
            aria-label="Add sticker"
            @click="toggleStickerPicker"
          >
            <Smile class="h-5 w-5" />
          </button>

          <div v-if="showStickerPicker" class="fixed inset-0 z-0" @click="showStickerPicker = false" />

          <div
            v-if="showStickerPicker"
            class="absolute left-0 z-10 mt-2 grid w-56 grid-cols-4 gap-1 rounded-lg border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
            @click.stop
          >
            <button
              v-for="sticker in stickerStore.stickers"
              :key="sticker.key"
              type="button"
              class="flex h-10 w-10 items-center justify-center rounded-md text-2xl transition-colors duration-200 hover:bg-zinc-100 dark:hover:bg-zinc-800"
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
