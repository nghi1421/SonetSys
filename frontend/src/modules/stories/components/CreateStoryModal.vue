<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Image, Video, X } from '@lucide/vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useStoryStore } from '../store/storyStore'
import type { StoryMediaType } from '../types'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ 'update:open': [boolean] }>()

const storyStore = useStoryStore()
const { t } = useI18n()

const caption = ref('')
const mediaFile = ref<File | null>(null)
const mediaType = ref<StoryMediaType | null>(null)
const mediaPreviewUrl = ref<string | null>(null)
const submitting = ref(false)
const error = ref<string | null>(null)

const photoInput = ref<HTMLInputElement | null>(null)
const videoInput = ref<HTMLInputElement | null>(null)

const canSubmit = computed(() => Boolean(mediaFile.value))

function clearMedia(): void {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
  mediaFile.value = null
  mediaType.value = null
  mediaPreviewUrl.value = null
}

function reset(): void {
  caption.value = ''
  error.value = null
  clearMedia()
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) reset()
  },
)

function close(): void {
  emit('update:open', false)
}

function pickPhoto(): void {
  photoInput.value?.click()
}

function pickVideo(): void {
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

onBeforeUnmount(() => {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
})

async function onSubmit(): Promise<void> {
  if (!mediaFile.value || !mediaType.value) {
    error.value = t('stories.createModal.mediaRequired')
    return
  }

  submitting.value = true
  error.value = null

  try {
    await storyStore.createStory({
      media: mediaFile.value,
      media_type: mediaType.value,
      caption: caption.value.trim() || undefined,
    })
    close()
  } catch {
    error.value = t('stories.createModal.publishError')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-cyber-bg/80 px-4 backdrop-blur-sm"
      @click="close"
    >
      <div class="w-full max-w-md rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md" @click.stop>
        <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
          {{ t('stories.createModal.title') }}
        </h2>

        <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhotoChange" />
        <input ref="videoInput" type="file" accept="video/*" class="hidden" @change="onVideoChange" />

        <div v-if="mediaPreviewUrl" class="relative mt-3 w-fit">
          <img
            v-if="mediaType === 'image'"
            :src="mediaPreviewUrl"
            :alt="t('stories.createModal.title')"
            class="max-h-72 rounded-hud border border-cyber-border"
          />
          <video
            v-else-if="mediaType === 'video'"
            :src="mediaPreviewUrl"
            controls
            class="max-h-72 rounded-hud border border-cyber-border"
          />
          <button
            type="button"
            class="absolute -right-2 -top-2 rounded-full border border-cyber-border bg-cyber-glass p-1 text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
            :aria-label="t('stories.createModal.removeMedia')"
            @click="clearMedia"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </div>

        <div v-else class="mt-3 flex gap-2">
          <button
            type="button"
            class="flex flex-1 flex-col items-center gap-1.5 rounded-hud border border-dashed border-cyber-border bg-cyber-surface/60 py-6 text-cyber-muted transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
            @click="pickPhoto"
          >
            <Image class="h-5 w-5" />
            <span class="font-mono text-[10px] uppercase tracking-widest">{{ t('stories.createModal.addPhoto') }}</span>
          </button>
          <button
            type="button"
            class="flex flex-1 flex-col items-center gap-1.5 rounded-hud border border-dashed border-cyber-border bg-cyber-surface/60 py-6 text-cyber-muted transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
            @click="pickVideo"
          >
            <Video class="h-5 w-5" />
            <span class="font-mono text-[10px] uppercase tracking-widest">{{ t('stories.createModal.addVideo') }}</span>
          </button>
        </div>

        <textarea
          v-model="caption"
          rows="2"
          maxlength="200"
          :placeholder="t('stories.createModal.captionPlaceholder')"
          class="mt-3 block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
        />

        <p v-if="error" class="mt-2 font-mono text-xs text-cyber-neon-pink">{{ error }}</p>

        <div class="mt-4 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
            @click="close"
          >
            {{ t('common.cancel') }}
          </button>
          <AppButton
            :label="t('stories.createModal.submit')"
            :loading="submitting"
            :disabled="!canSubmit"
            @click="onSubmit"
          />
        </div>
      </div>
    </div>
  </Teleport>
</template>
