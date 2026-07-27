<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { EllipsisVertical, Flag, MessageCircle, Trash2, Volume2, VolumeX } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import CommentThread from '@/modules/feed/components/CommentThread.vue'
import LinkifiedText from '@/modules/feed/components/LinkifiedText.vue'
import ReactionButton from '@/modules/feed/components/ReactionButton.vue'
import ShareMenu from '@/modules/feed/components/ShareMenu.vue'
import { useFeedStore } from '@/modules/feed/store/feedStore'
import type { ReactionType } from '@/modules/feed/types'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import ReportDialog from '@/shared/components/ui/ReportDialog.vue'
import { useReelStore } from '../store/reelStore'
import type { Reel } from '../types'

const props = defineProps<{ reel: Reel }>()

const feedStore = useFeedStore()
const reelStore = useReelStore()
const authStore = useAuthStore()
const { t } = useI18n()

const cardRef = ref<HTMLElement | null>(null)
const videoRef = ref<HTMLVideoElement | null>(null)
const muted = ref(true)
const showComments = ref(false)
const showActionsMenu = ref(false)
const showReportDialog = ref(false)
const confirmingDelete = ref(false)
let observer: IntersectionObserver | null = null

const isOwner = computed(() => authStore.user?.id === props.reel.author.id)
const canModerate = computed(
  () => authStore.user?.role.slug === 'admin' || authStore.user?.role.slug === 'moderator',
)
const canDelete = computed(() => isOwner.value || canModerate.value)

// commentsByPost is only populated once fetchComments runs for this reel —
// falls back to the server-provided count until then, so the badge never
// briefly shows 0 before the thread is opened.
const commentsCount = computed(() => feedStore.commentsByPost[props.reel.id]?.length ?? props.reel.comments_count)

onMounted(() => {
  observer = new IntersectionObserver(
    ([entry]) => {
      const video = videoRef.value
      if (!video || !entry) return
      if (entry.isIntersecting) {
        video.play().catch(() => {})
      } else {
        video.pause()
      }
    },
    { threshold: 0.6 },
  )
  if (cardRef.value) observer.observe(cardRef.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
})

function toggleMute(): void {
  muted.value = !muted.value
}

async function onReact(type: ReactionType): Promise<void> {
  await reelStore.reactToReel(props.reel.id, type)
}

async function onUnreact(): Promise<void> {
  await reelStore.unreactToReel(props.reel.id)
}

async function onToggleComments(): Promise<void> {
  showComments.value = !showComments.value
  if (showComments.value && !feedStore.commentsByPost[props.reel.id]) {
    await feedStore.fetchComments(props.reel.id)
  }
}

function onDeleteClick(): void {
  showActionsMenu.value = false
  confirmingDelete.value = true
}

function onReportClick(): void {
  showActionsMenu.value = false
  showReportDialog.value = true
}

async function onConfirmDelete(): Promise<void> {
  confirmingDelete.value = false
  await reelStore.deleteReel(props.reel.id)
}
</script>

<template>
  <section ref="cardRef" class="relative h-[100dvh] w-full snap-start snap-always bg-black">
    <div
      class="relative mx-auto flex h-full w-full items-center justify-center overflow-hidden sm:aspect-[9/16] sm:h-full sm:w-auto"
    >
      <video
        ref="videoRef"
        :src="reel.media_url ?? undefined"
        class="max-h-full max-w-full object-contain"
        loop
        playsinline
        :muted="muted"
        @click="toggleMute"
      />
    </div>

    <div class="absolute right-4 top-4 z-10 flex items-center gap-2">
      <button
        type="button"
        class="rounded-full bg-black/40 p-2 text-white backdrop-blur-md transition-all duration-300 hover:bg-black/60"
        :aria-label="muted ? t('reels.card.unmute') : t('reels.card.mute')"
        @click="toggleMute"
      >
        <VolumeX v-if="muted" class="h-4 w-4" />
        <Volume2 v-else class="h-4 w-4" />
      </button>

      <div class="relative">
        <button
          type="button"
          class="rounded-full bg-black/40 p-2 text-white backdrop-blur-md transition-all duration-300 hover:bg-black/60"
          :aria-label="t('feed.postCard.actionsLabel')"
          @click="showActionsMenu = !showActionsMenu"
        >
          <EllipsisVertical class="h-4 w-4" />
        </button>

        <div v-if="showActionsMenu" class="fixed inset-0 z-0" @click="showActionsMenu = false" />

        <div
          v-if="showActionsMenu"
          class="popover-panel absolute right-0 z-10 mt-1 w-36 rounded-hud border border-cyber-border bg-cyber-surface py-1"
          @click.stop
        >
          <button
            v-if="canDelete"
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-left font-mono text-xs text-cyber-neon-pink transition-colors duration-300 hover:shadow-pink-glow"
            @click="onDeleteClick"
          >
            <Trash2 class="h-3.5 w-3.5" /> {{ t('common.delete') }}
          </button>
          <button
            v-if="!isOwner"
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-left font-mono text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-pink"
            @click="onReportClick"
          >
            <Flag class="h-3.5 w-3.5" /> {{ t('report.action') }}
          </button>
        </div>
      </div>
    </div>

    <div class="absolute inset-x-0 bottom-0 z-10 flex items-end justify-between gap-3 bg-gradient-to-t from-black/70 to-transparent p-4">
      <div class="min-w-0 flex-1">
        <router-link
          v-if="reel.author.id"
          :to="`/users/${reel.author.id}`"
          class="font-mono text-xs font-bold uppercase tracking-widest text-white drop-shadow transition-colors duration-300 hover:text-cyber-neon-cyan"
        >
          {{ reel.author.name }}
        </router-link>
        <p v-if="reel.body" class="mt-1 font-mono text-xs text-white/90 drop-shadow">
          <LinkifiedText :text="reel.body" :hashtags="reel.hashtags" :mentions="reel.mentions" />
        </p>
      </div>

      <div class="flex shrink-0 flex-col items-center gap-3">
        <ReactionButton
          :count="reel.likes_count"
          :my-reaction="reel.my_reaction"
          variant="pill"
          @react="onReact"
          @unreact="onUnreact"
        />

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-black/40 px-2.5 py-1 font-mono text-[10px] text-white backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/40 hover:text-cyber-neon-cyan"
          @click="onToggleComments"
        >
          <MessageCircle class="h-3 w-3" />
          {{ commentsCount }}
        </button>

        <ShareMenu :post="reel" />
      </div>
    </div>

    <div
      v-if="showComments"
      class="absolute inset-x-0 bottom-0 z-20 max-h-[70dvh] overflow-y-auto rounded-t-hud border-t border-cyber-border bg-cyber-surface p-4"
    >
      <div class="flex items-center justify-between">
        <p class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
          {{ t('reels.card.comments') }}
        </p>
        <button
          type="button"
          class="font-mono text-[10px] uppercase text-cyber-muted hover:text-cyber-neon-cyan"
          @click="showComments = false"
        >
          {{ t('common.close') }}
        </button>
      </div>
      <CommentThread :post-id="reel.id" />
    </div>

    <ConfirmDialog
      :open="confirmingDelete"
      :title="t('feed.postCard.confirmDeleteTitle')"
      :message="t('feed.postCard.confirmDeleteMessage')"
      @confirm="onConfirmDelete"
      @cancel="confirmingDelete = false"
    />

    <ReportDialog :open="showReportDialog" type="post" :id="reel.id" @update:open="showReportDialog = $event" />
  </section>
</template>
