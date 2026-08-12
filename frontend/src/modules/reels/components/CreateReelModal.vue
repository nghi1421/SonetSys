<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Video, X } from '@lucide/vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
import AppTextarea from '@/shared/components/ui/AppTextarea.vue'
import { useReelStore } from '../store/reelStore'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ 'update:open': [boolean] }>()

const reelStore = useReelStore()
const { t } = useI18n()

const caption = ref('')
const mediaFile = ref<File | null>(null)
const mediaPreviewUrl = ref<string | null>(null)
const submitting = ref(false)
const error = ref<string | null>(null)

const videoInput = ref<HTMLInputElement | null>(null)

const canSubmit = computed(() => Boolean(mediaFile.value))

function clearMedia(): void {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
  mediaFile.value = null
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

function pickVideo(): void {
  videoInput.value?.click()
}

function onVideoChange(event: Event): void {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  clearMedia()
  mediaFile.value = file
  mediaPreviewUrl.value = URL.createObjectURL(file)
}

onBeforeUnmount(() => {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
})

async function onSubmit(): Promise<void> {
  if (!mediaFile.value) {
    error.value = t('reels.createModal.mediaRequired')
    return
  }

  submitting.value = true
  error.value = null

  try {
    await reelStore.createReel({
      media: mediaFile.value,
      body: caption.value.trim() || undefined,
    })
    close()
  } catch {
    error.value = t('reels.createModal.publishError')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AppModal :open="open" size="md" @close="close">
 <h2 class="text-xs font-bold text-cyber-text">
      {{ t('reels.createModal.title') }}
    </h2>

    <input ref="videoInput" type="file" accept="video/*" class="hidden" @change="onVideoChange" />

    <div v-if="mediaPreviewUrl" class="relative mt-3 w-fit">
      <video
        :src="mediaPreviewUrl"
        controls
        class="max-h-72 rounded-hud border border-cyber-border"
      />
      <button
        type="button"
        class="absolute -right-2 -top-2 rounded-full border border-cyber-border bg-cyber-glass p-1 text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
        :aria-label="t('reels.createModal.removeMedia')"
        @click="clearMedia"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <button
      v-else
      type="button"
      class="mt-3 flex w-full flex-col items-center gap-1.5 rounded-hud border border-dashed border-cyber-border bg-cyber-surface/60 py-8 text-cyber-muted transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
      @click="pickVideo"
    >
      <Video class="h-6 w-6" />
 <span class="text-xs">{{
        t('reels.createModal.addVideo')
      }}</span>
    </button>

    <AppTextarea
      v-model="caption"
      class="mt-3"
      :rows="2"
      :maxlength="200"
      :placeholder="t('reels.createModal.captionPlaceholder')"
    />

 <p v-if="error" class="mt-2 text-xs text-cyber-neon-pink">{{ error }}</p>

    <div class="mt-4 flex justify-end gap-3">
      <button
        type="button"
 class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
        @click="close"
      >
        {{ t('common.cancel') }}
      </button>
      <AppButton
        :label="t('reels.createModal.submit')"
        :loading="submitting"
        :disabled="!canSubmit"
        @click="onSubmit"
      />
    </div>
  </AppModal>
</template>
