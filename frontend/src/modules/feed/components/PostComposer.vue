<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Image, MapPin, Smile, UserPlus, UserRound, Video, X } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
import AppSelect from '@/shared/components/ui/AppSelect.vue'
import { locationApi } from '../api/locationApi'
import { useMentionPicker } from '../composables/useMentionPicker'
import { useStickerStore } from '../store/stickerStore'
import type { CreatePostPayload, MentionCandidate, PostLocation, PostVisibility } from '../types'

const CHECK_IN_SEARCH_DEBOUNCE_MS = 400
const CHECK_IN_MIN_QUERY_LENGTH = 2

const props = defineProps<{ onSubmit: (payload: CreatePostPayload) => Promise<void> }>()

const stickerStore = useStickerStore()
const authStore = useAuthStore()
const { t } = useI18n()

const open = ref(false)

function openComposer(): void {
  open.value = true
}

function closeComposer(): void {
  open.value = false
}

const body = ref('')
const loading = ref(false)
const error = ref<string | null>(null)
const visibility = ref<PostVisibility>('members')

const mediaFile = ref<File | null>(null)
const mediaType = ref<'image' | 'video' | null>(null)
const mediaPreviewUrl = ref<string | null>(null)
const selectedStickerKey = ref<string | null>(null)
const showStickerPicker = ref(false)

const showCheckInPicker = ref(false)
const checkInQuery = ref('')
const checkInResults = ref<PostLocation[]>([])
const checkInSearching = ref(false)
const selectedLocation = ref<PostLocation | null>(null)
let checkInDebounceTimer: ReturnType<typeof setTimeout> | undefined

const mention = useMentionPicker()
const mentionedUserIds = ref<number[]>([])

const photoInput = ref<HTMLInputElement | null>(null)
const videoInput = ref<HTMLInputElement | null>(null)

const POPOVER_GAP_PX = 8

const stickerBtnRef = ref<HTMLElement | null>(null)
const checkInBtnRef = ref<HTMLElement | null>(null)
const mentionBtnRef = ref<HTMLElement | null>(null)

const stickerPos = ref({ top: 0, left: 0 })
const checkInPos = ref({ top: 0, left: 0 })
const mentionPos = ref({ top: 0, left: 0 })

function popoverPosition(trigger: HTMLElement | null): { top: number; left: number } {
  if (!trigger) return { top: 0, left: 0 }
  const rect = trigger.getBoundingClientRect()
  return { top: rect.bottom + POPOVER_GAP_PX, left: rect.left }
}

const selectedStickerEmoji = computed(
  () =>
    stickerStore.stickers.find((sticker) => sticker.key === selectedStickerKey.value)?.emoji ??
    null,
)

const canSubmit = computed(() =>
  Boolean(
    body.value.trim() || mediaFile.value || selectedStickerKey.value || selectedLocation.value,
  ),
)

function clearMedia(): void {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
  mediaFile.value = null
  mediaType.value = null
  mediaPreviewUrl.value = null
  selectedStickerKey.value = null
}

function pickPhoto(): void {
  showStickerPicker.value = false
  showCheckInPicker.value = false
  mention.close()
  photoInput.value?.click()
}

function pickVideo(): void {
  showStickerPicker.value = false
  showCheckInPicker.value = false
  mention.close()
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
  showCheckInPicker.value = false
  mention.close()
  showStickerPicker.value = !showStickerPicker.value
  if (showStickerPicker.value) {
    stickerPos.value = popoverPosition(stickerBtnRef.value)
    await stickerStore.fetchStickers()
  }
}

function selectSticker(key: string): void {
  clearMedia()
  selectedStickerKey.value = key
  showStickerPicker.value = false
}

function toggleCheckInPicker(): void {
  showStickerPicker.value = false
  mention.close()
  showCheckInPicker.value = !showCheckInPicker.value
  if (showCheckInPicker.value) {
    checkInPos.value = popoverPosition(checkInBtnRef.value)
  }
}

function closeCheckInPicker(): void {
  showCheckInPicker.value = false
}

function toggleMentionPicker(): void {
  showStickerPicker.value = false
  showCheckInPicker.value = false
  mention.toggle()
  if (mention.showPicker.value) {
    mentionPos.value = popoverPosition(mentionBtnRef.value)
  }
}

function selectMention(candidate: MentionCandidate): void {
  const trimmedBody = body.value.trimEnd()
  body.value = trimmedBody.length ? `${trimmedBody} @${candidate.name} ` : `@${candidate.name} `
  mentionedUserIds.value.push(candidate.id)
  mention.reset()
}

function onCheckInSearchInput(): void {
  if (checkInDebounceTimer) clearTimeout(checkInDebounceTimer)

  const query = checkInQuery.value.trim()
  if (query.length < CHECK_IN_MIN_QUERY_LENGTH) {
    checkInResults.value = []
    checkInSearching.value = false
    return
  }

  checkInDebounceTimer = setTimeout(async () => {
    checkInSearching.value = true
    try {
      const response = await locationApi.search(query)
      checkInResults.value = response.data ?? []
    } catch {
      checkInResults.value = []
    } finally {
      checkInSearching.value = false
    }
  }, CHECK_IN_SEARCH_DEBOUNCE_MS)
}

function selectLocation(location: PostLocation): void {
  selectedLocation.value = location
  showCheckInPicker.value = false
  checkInQuery.value = ''
  checkInResults.value = []
}

function clearLocation(): void {
  selectedLocation.value = null
}

onBeforeUnmount(() => {
  if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value)
  if (checkInDebounceTimer) clearTimeout(checkInDebounceTimer)
  mention.dispose()
})

async function handleSubmit(): Promise<void> {
  if (!canSubmit.value) return

  loading.value = true
  error.value = null

  try {
    await props.onSubmit({
      body: body.value.trim(),
      visibility: visibility.value,
      media: mediaFile.value ?? undefined,
      media_type: mediaType.value ?? undefined,
      sticker_key: selectedStickerKey.value ?? undefined,
      location_name: selectedLocation.value?.name,
      location_lat: selectedLocation.value?.lat,
      location_lng: selectedLocation.value?.lng,
      mentioned_user_ids: mentionedUserIds.value.length ? [...mentionedUserIds.value] : undefined,
    })
    body.value = ''
    visibility.value = 'members'
    clearMedia()
    clearLocation()
    mentionedUserIds.value = []
    closeComposer()
  } catch {
    error.value = t('feed.postComposer.publishError')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <button
    type="button"
    class="flex w-full items-center gap-3 rounded-hud border border-cyber-border bg-cyber-glass p-4 text-left backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
    @click="openComposer"
  >
    <img
      v-if="authStore.user?.avatar_url"
      :src="authStore.user.avatar_url"
      :alt="authStore.user.name ?? ''"
      class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-cyber-neon-indigo"
    />
    <span
      v-else
      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyber-surface ring-2 ring-cyber-neon-indigo"
    >
      <UserRound class="h-4 w-4 text-cyber-neon-cyan" />
    </span>
    <span
      class="flex-1 rounded-full border border-cyber-border bg-cyber-surface/60 px-4 py-2 font-mono text-xs text-cyber-muted"
    >
      {{ t('feed.postComposer.placeholder') }}
    </span>
  </button>

  <AppModal :open="open" size="lg" panel-class="p-6" @close="closeComposer">
    <form class="relative space-y-4 has-[.popover-panel]:z-20" @submit.prevent="handleSubmit">
      <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
        {{ t('feed.postComposer.title') }}
      </h2>

      <textarea
        v-model="body"
        rows="6"
        :placeholder="t('feed.postComposer.placeholder')"
        class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-4 py-3 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
      />

      <AppSelect
        v-model="visibility"
        :label="t('feed.postComposer.visibilityLabel')"
        class="w-48"
      >
        <option value="public">{{ t('feed.postComposer.visibilityOptions.public') }}</option>
        <option value="members">{{ t('feed.postComposer.visibilityOptions.members') }}</option>
        <option value="private">{{ t('feed.postComposer.visibilityOptions.private') }}</option>
      </AppSelect>

      <div v-if="mediaPreviewUrl" class="relative w-fit">
        <img
          v-if="mediaType === 'image'"
          :src="mediaPreviewUrl"
          :alt="t('feed.postComposer.photoPreviewAlt')"
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
          :aria-label="t('feed.postComposer.removeMedia')"
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
          :aria-label="t('feed.postComposer.removeSticker')"
          @click="clearMedia"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      </div>

      <div
        v-if="selectedLocation"
        class="flex w-fit items-center gap-1.5 rounded-full border border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 px-3 py-1"
      >
        <MapPin class="h-3 w-3 text-cyber-neon-cyan" />
        <span class="font-mono text-xs text-cyber-neon-cyan">{{ selectedLocation.name }}</span>
        <button
          type="button"
          class="text-cyber-neon-cyan transition-colors duration-300 hover:text-cyber-neon-pink"
          :aria-label="t('feed.postComposer.checkInClear')"
          @click="clearLocation"
        >
          <X class="h-3 w-3" />
        </button>
      </div>

      <p v-if="error" class="font-mono text-xs text-cyber-neon-pink">{{ error }}</p>

      <div class="flex items-center justify-between">
        <div class="flex items-center gap-1">
          <input
            ref="photoInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onPhotoChange"
          />
          <input
            ref="videoInput"
            type="file"
            accept="video/*"
            class="hidden"
            @change="onVideoChange"
          />

          <button
            type="button"
            class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
            :aria-label="t('feed.postComposer.addPhoto')"
            @click="pickPhoto"
          >
            <Image class="h-5 w-5" />
          </button>
          <button
            type="button"
            class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
            :aria-label="t('feed.postComposer.addVideo')"
            @click="pickVideo"
          >
            <Video class="h-5 w-5" />
          </button>

          <div class="relative">
            <button
              ref="stickerBtnRef"
              type="button"
              class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
              :aria-label="t('feed.postComposer.addSticker')"
              @click="toggleStickerPicker"
            >
              <Smile class="h-5 w-5" />
            </button>

            <Teleport to="body">
              <div
                v-if="showStickerPicker"
                class="fixed inset-0 z-[55]"
                @click="showStickerPicker = false"
              />

              <div
                v-if="showStickerPicker"
                class="popover-panel fixed z-[60] grid w-44 grid-cols-3 gap-1 rounded-hud border border-cyber-border bg-cyber-surface p-2 sm:w-56 sm:grid-cols-4"
                :style="{ top: `${stickerPos.top}px`, left: `${stickerPos.left}px` }"
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
            </Teleport>
          </div>

          <div class="relative">
            <button
              ref="checkInBtnRef"
              type="button"
              class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
              :aria-label="t('feed.postComposer.checkIn')"
              @click="toggleCheckInPicker"
            >
              <MapPin class="h-5 w-5" />
            </button>

            <Teleport to="body">
              <div
                v-if="showCheckInPicker"
                class="fixed inset-0 z-[55]"
                @click="closeCheckInPicker"
              />

              <div
                v-if="showCheckInPicker"
                class="popover-panel fixed z-[60] w-64 rounded-hud border border-cyber-border bg-cyber-surface p-3"
                :style="{ top: `${checkInPos.top}px`, left: `${checkInPos.left}px` }"
                @click.stop
              >
                <input
                  v-model="checkInQuery"
                  type="text"
                  :placeholder="t('feed.postComposer.checkInSearchPlaceholder')"
                  class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
                  @input="onCheckInSearchInput"
                />

                <ul v-if="checkInResults.length" class="mt-2 max-h-48 space-y-1 overflow-y-auto">
                  <li v-for="result in checkInResults" :key="`${result.lat}-${result.lng}`">
                    <button
                      type="button"
                      class="block w-full rounded-hud px-2 py-1.5 text-left font-mono text-xs text-cyber-text transition-all duration-300 hover:bg-cyber-surface/60 hover:text-cyber-neon-cyan"
                      @click="selectLocation(result)"
                    >
                      {{ result.name }}
                    </button>
                  </li>
                </ul>
                <p v-else-if="checkInSearching" class="mt-2 font-mono text-xs text-cyber-muted">
                  {{ t('common.loading') }}
                </p>
                <p
                  v-else-if="checkInQuery.trim().length >= 2"
                  class="mt-2 font-mono text-xs text-cyber-muted"
                >
                  {{ t('feed.postComposer.checkInNoResults') }}
                </p>
              </div>
            </Teleport>
          </div>

          <div class="relative">
            <button
              ref="mentionBtnRef"
              type="button"
              class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
              :aria-label="t('feed.postComposer.mention')"
              @click="toggleMentionPicker"
            >
              <UserPlus class="h-5 w-5" />
            </button>

            <Teleport to="body">
              <div
                v-if="mention.showPicker.value"
                class="fixed inset-0 z-[55]"
                @click="mention.close()"
              />

              <div
                v-if="mention.showPicker.value"
                class="popover-panel fixed z-[60] w-64 rounded-hud border border-cyber-border bg-cyber-surface p-3"
                :style="{ top: `${mentionPos.top}px`, left: `${mentionPos.left}px` }"
                @click.stop
              >
                <input
                  v-model="mention.query.value"
                  type="text"
                  :placeholder="t('feed.postComposer.mentionSearchPlaceholder')"
                  class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
                  @input="mention.onSearchInput()"
                />

                <ul
                  v-if="mention.results.value.length"
                  class="mt-2 max-h-48 space-y-1 overflow-y-auto"
                >
                  <li v-for="candidate in mention.results.value" :key="candidate.id">
                    <button
                      type="button"
                      class="block w-full rounded-hud px-2 py-1.5 text-left font-mono text-xs text-cyber-text transition-all duration-300 hover:bg-cyber-surface/60 hover:text-cyber-neon-cyan"
                      @click="selectMention(candidate)"
                    >
                      {{ candidate.name }}
                    </button>
                  </li>
                </ul>
                <p
                  v-else-if="mention.searching.value"
                  class="mt-2 font-mono text-xs text-cyber-muted"
                >
                  {{ t('common.loading') }}
                </p>
                <p
                  v-else-if="mention.query.value.trim().length >= 2"
                  class="mt-2 font-mono text-xs text-cyber-muted"
                >
                  {{ t('feed.postComposer.mentionNoResults') }}
                </p>
              </div>
            </Teleport>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="button"
            class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
            @click="closeComposer"
          >
            {{ t('common.cancel') }}
          </button>
          <AppButton
            type="submit"
            :label="t('feed.postComposer.submit')"
            :loading="loading"
            :disabled="!canSubmit"
          />
        </div>
      </div>
    </form>
  </AppModal>
</template>
