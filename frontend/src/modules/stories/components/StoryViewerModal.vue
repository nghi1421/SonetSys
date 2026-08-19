<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, ChevronRight, Eye, Pause, Trash2, X } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useStoryStore } from '../store/storyStore'
import StoryViewersList from './StoryViewersList.vue'

const IMAGE_DURATION_MS = 5000
const PROGRESS_TICK_MS = 50
const HOLD_TO_PAUSE_MS = 250

const storyStore = useStoryStore()
const authStore = useAuthStore()
const { t } = useI18n()

const videoRef = ref<HTMLVideoElement | null>(null)
const progress = ref(0)
const showViewers = ref(false)
const confirmingDelete = ref(false)
const isPaused = ref(false)
let progressTimer: ReturnType<typeof setInterval> | null = null
let holdTimer: ReturnType<typeof setTimeout> | null = null
let heldLongEnoughToPause = false

const isOpen = computed(() => storyStore.activeViewerGroupIndex !== null)
const activeGroup = computed(() =>
  storyStore.activeViewerGroupIndex !== null
    ? (storyStore.storyGroups[storyStore.activeViewerGroupIndex] ?? null)
    : null,
)
const currentStory = computed(
  () => activeGroup.value?.stories[storyStore.activeViewerStoryIndex] ?? null,
)
const isOwner = computed(() => currentStory.value?.author.id === authStore.user?.id)
const canModerate = computed(
  () => authStore.user?.role.slug === 'admin' || authStore.user?.role.slug === 'moderator',
)
const canDelete = computed(() => isOwner.value || canModerate.value)
const currentViewers = computed(() =>
  currentStory.value ? (storyStore.viewersByStory[currentStory.value.id] ?? []) : [],
)

function stopProgressTimer(): void {
  if (progressTimer) {
    clearInterval(progressTimer)
    progressTimer = null
  }
}

function close(): void {
  stopProgressTimer()
  showViewers.value = false
  confirmingDelete.value = false
  storyStore.closeViewer()
}

function next(): void {
  if (!activeGroup.value || storyStore.activeViewerGroupIndex === null) return

  if (storyStore.activeViewerStoryIndex < activeGroup.value.stories.length - 1) {
    storyStore.activeViewerStoryIndex += 1
    return
  }

  const nextGroupIndex = storyStore.activeViewerGroupIndex + 1
  if (nextGroupIndex < storyStore.storyGroups.length) {
    storyStore.openViewer(nextGroupIndex)
  } else {
    close()
  }
}

function prev(): void {
  if (storyStore.activeViewerGroupIndex === null) return

  if (storyStore.activeViewerStoryIndex > 0) {
    storyStore.activeViewerStoryIndex -= 1
    return
  }

  const prevGroupIndex = storyStore.activeViewerGroupIndex - 1
  const prevGroup = storyStore.storyGroups[prevGroupIndex]
  if (prevGroup) {
    storyStore.openViewer(prevGroupIndex)
    storyStore.activeViewerStoryIndex = prevGroup.stories.length - 1
  }
}

function startImageProgress(resetProgress = true): void {
  stopProgressTimer()
  if (resetProgress) progress.value = 0
  progressTimer = setInterval(() => {
    progress.value += (PROGRESS_TICK_MS / IMAGE_DURATION_MS) * 100
    if (progress.value >= 100) {
      progress.value = 100
      next()
    }
  }, PROGRESS_TICK_MS)
}

function onVideoTimeUpdate(): void {
  const video = videoRef.value
  if (!video || !video.duration) return
  progress.value = (video.currentTime / video.duration) * 100
}

function pauseStory(): void {
  if (isPaused.value) return
  isPaused.value = true
  stopProgressTimer()
  videoRef.value?.pause()
}

function resumeStory(): void {
  if (!isPaused.value) return
  isPaused.value = false
  if (currentStory.value?.media_type === 'image') {
    startImageProgress(false)
  } else {
    videoRef.value?.play()
  }
}

function clearHoldTimer(): void {
  if (holdTimer) {
    clearTimeout(holdTimer)
    holdTimer = null
  }
}

// A quick tap navigates prev/next as before; holding down (past
// HOLD_TO_PAUSE_MS) pauses the story instead — releasing resumes it rather
// than navigating, matching the press-and-hold pattern from Instagram/
// Facebook Stories. Without this there was no way to stop an auto-advancing
// story at all.
function onZonePointerDown(): void {
  heldLongEnoughToPause = false
  clearHoldTimer()
  holdTimer = setTimeout(() => {
    heldLongEnoughToPause = true
    pauseStory()
  }, HOLD_TO_PAUSE_MS)
}

function onZonePointerUp(direction: 'prev' | 'next'): void {
  clearHoldTimer()
  if (heldLongEnoughToPause) {
    resumeStory()
    heldLongEnoughToPause = false
    return
  }
  if (direction === 'prev') {
    prev()
  } else {
    next()
  }
}

function onZonePointerCancel(): void {
  clearHoldTimer()
  if (heldLongEnoughToPause) {
    resumeStory()
    heldLongEnoughToPause = false
  }
}

watch(currentStory, async (story) => {
  stopProgressTimer()
  clearHoldTimer()
  progress.value = 0
  isPaused.value = false
  heldLongEnoughToPause = false
  showViewers.value = false

  if (!story) return

  if (!story.viewed_by_me && story.author.id !== authStore.user?.id) {
    storyStore.markViewed(story.id)
  }

  if (story.media_type === 'image') {
    startImageProgress()
  } else {
    await nextTick()
    videoRef.value?.play()
  }
})

async function openViewers(): Promise<void> {
  if (!currentStory.value) return
  stopProgressTimer()
  await storyStore.fetchViewers(currentStory.value.id)
  showViewers.value = true
}

async function onConfirmDelete(): Promise<void> {
  if (!currentStory.value) return
  confirmingDelete.value = false
  const storyId = currentStory.value.id
  const wasLast = activeGroup.value?.stories.length === 1
  await storyStore.deleteStory(storyId)
  if (wasLast) {
    close()
  } else if (storyStore.activeViewerStoryIndex >= (activeGroup.value?.stories.length ?? 0)) {
    storyStore.activeViewerStoryIndex = Math.max(0, (activeGroup.value?.stories.length ?? 1) - 1)
  }
}

onBeforeUnmount(() => {
  stopProgressTimer()
  clearHoldTimer()
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen && currentStory && activeGroup" class="fixed inset-0 z-50 bg-black">
      <div class="absolute inset-x-0 top-0 z-10 flex gap-1 p-3">
        <div
          v-for="(story, index) in activeGroup.stories"
          :key="story.id"
          class="h-0.5 flex-1 overflow-hidden rounded-full bg-white/30"
        >
          <div
            class="h-full bg-cyber-neon-cyan transition-[width] duration-100 ease-linear"
            :style="{
              width:
                index < storyStore.activeViewerStoryIndex
                  ? '100%'
                  : index === storyStore.activeViewerStoryIndex
                    ? `${progress}%`
                    : '0%',
            }"
          />
        </div>
      </div>

      <div class="absolute inset-x-0 top-6 z-10 flex items-center justify-between px-4">
        <span class="text-xs font-bold text-white drop-shadow">
          {{ activeGroup.author.name }}
        </span>
        <div class="flex items-center gap-2">
          <button
            v-if="canDelete"
            type="button"
            class="rounded-full bg-black/40 p-2 text-white transition-all duration-300 hover:text-cyber-neon-pink"
            :aria-label="t('stories.viewer.deleteStory')"
            @click="confirmingDelete = true"
          >
            <Trash2 class="h-4 w-4" />
          </button>
          <button
            type="button"
            class="rounded-full bg-black/40 p-2 text-white transition-all duration-300 hover:text-cyber-neon-cyan"
            :aria-label="t('common.cancel')"
            @click="close"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div class="relative flex h-full w-full items-center justify-center">
        <div
          class="relative flex h-full w-full items-center justify-center overflow-hidden sm:mx-auto sm:aspect-[9/16] sm:h-[min(90vh,800px)] sm:w-auto sm:rounded-hud sm:border sm:border-white/10 sm:shadow-2xl"
        >
          <img
            v-if="currentStory.media_type === 'image'"
            :src="currentStory.media_url"
            :alt="currentStory.caption ?? ''"
            class="max-h-full max-w-full object-contain"
          />
          <video
            v-else
            ref="videoRef"
            :src="currentStory.media_url"
            class="max-h-full max-w-full object-contain"
            playsinline
            autoplay
            @timeupdate="onVideoTimeUpdate"
            @ended="next"
          />
        </div>

        <div
          v-if="isPaused"
          class="pointer-events-none absolute inset-0 flex items-center justify-center"
        >
          <span class="rounded-full bg-black/50 p-4">
            <Pause class="h-8 w-8 text-white" />
          </span>
        </div>

        <button
          type="button"
          class="absolute inset-y-0 left-0 w-1/3"
          :aria-label="t('common.previous')"
          @pointerdown="onZonePointerDown"
          @pointerup="onZonePointerUp('prev')"
          @pointercancel="onZonePointerCancel"
        />
        <button
          type="button"
          class="absolute inset-y-0 right-0 w-1/3"
          :aria-label="t('common.next')"
          @pointerdown="onZonePointerDown"
          @pointerup="onZonePointerUp('next')"
          @pointercancel="onZonePointerCancel"
        />

        <button
          type="button"
          class="absolute left-4 top-1/2 z-10 flex -translate-y-1/2 rounded-full bg-black/40 p-2 text-white backdrop-blur-md transition-all duration-300 hover:bg-black/60 hover:text-cyber-neon-cyan"
          :aria-label="t('common.previous')"
          @click="prev"
        >
          <ChevronLeft class="h-6 w-6" />
        </button>
        <button
          type="button"
          class="absolute right-4 top-1/2 z-10 flex -translate-y-1/2 rounded-full bg-black/40 p-2 text-white backdrop-blur-md transition-all duration-300 hover:bg-black/60 hover:text-cyber-neon-cyan"
          :aria-label="t('common.next')"
          @click="next"
        >
          <ChevronRight class="h-6 w-6" />
        </button>
      </div>

      <div
        v-if="currentStory.caption || (isOwner && currentStory.views_count !== null)"
        class="absolute inset-x-0 bottom-0 z-10 flex items-center justify-between gap-3 bg-gradient-to-t from-black/70 to-transparent p-4"
      >
        <p v-if="currentStory.caption" class="text-xs text-white drop-shadow">
          {{ currentStory.caption }}
        </p>
        <button
          v-if="isOwner && currentStory.views_count !== null"
          type="button"
          class="ml-auto flex items-center gap-1.5 rounded-full bg-black/40 px-3 py-1.5 text-xs text-white transition-all duration-300 hover:text-cyber-neon-cyan"
          @click="openViewers"
        >
          <Eye class="h-3.5 w-3.5" />
          {{ t('stories.viewer.viewersCount', { count: currentStory.views_count }) }}
        </button>
      </div>

      <StoryViewersList
        :open="showViewers"
        :viewers="currentViewers"
        @close="showViewers = false"
      />

      <ConfirmDialog
        :open="confirmingDelete"
        :title="t('stories.viewer.confirmDeleteTitle')"
        :message="t('stories.viewer.confirmDeleteMessage')"
        @confirm="onConfirmDelete"
        @cancel="confirmingDelete = false"
      />
    </div>
  </Teleport>
</template>
