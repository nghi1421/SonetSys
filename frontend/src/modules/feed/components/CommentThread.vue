<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Trash2, UserPlus } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppButton from '@/shared/components/ui/AppButton.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useMentionPicker } from '../composables/useMentionPicker'
import LinkifiedText from './LinkifiedText.vue'
import ReactionButton from './ReactionButton.vue'
import { useFeedStore } from '../store/feedStore'
import type { Comment, MentionCandidate, ReactionType } from '../types'

const props = defineProps<{ postId: number }>()

const feedStore = useFeedStore()
const authStore = useAuthStore()
const { t } = useI18n()

const newComment = ref('')
const replyingTo = ref<number | null>(null)
const submitting = ref(false)
const pendingDeleteId = ref<number | null>(null)

const mention = useMentionPicker()
const mentionedUserIds = ref<number[]>([])

function toggleMentionPicker(): void {
  mention.toggle()
}

function selectMention(candidate: MentionCandidate): void {
  const trimmed = newComment.value.trimEnd()
  newComment.value = trimmed.length ? `${trimmed} @${candidate.name} ` : `@${candidate.name} `
  mentionedUserIds.value.push(candidate.id)
  mention.reset()
}

onBeforeUnmount(() => {
  mention.dispose()
})

const comments = computed(() => feedStore.commentsByPost[props.postId] ?? [])
const topLevel = computed(() => comments.value.filter((c) => c.parent_id === null))

function repliesFor(commentId: number): Comment[] {
  return comments.value.filter((c) => c.parent_id === commentId)
}

function canDelete(comment: Comment): boolean {
  return (
    authStore.user?.id === comment.author.id ||
    authStore.user?.role.slug === 'admin' ||
    authStore.user?.role.slug === 'moderator'
  )
}

async function submitComment(parentId?: number): Promise<void> {
  if (!newComment.value.trim()) return

  submitting.value = true
  try {
    await feedStore.createComment(
      props.postId,
      newComment.value.trim(),
      parentId,
      mentionedUserIds.value.length ? [...mentionedUserIds.value] : undefined,
    )
    newComment.value = ''
    replyingTo.value = null
    mentionedUserIds.value = []
  } finally {
    submitting.value = false
  }
}

async function onReact(commentId: number, type: ReactionType): Promise<void> {
  await feedStore.reactToComment(props.postId, commentId, type)
}

async function onUnreact(commentId: number): Promise<void> {
  await feedStore.unreactToComment(props.postId, commentId)
}

async function onConfirmDelete(): Promise<void> {
  if (pendingDeleteId.value === null) return
  await feedStore.deleteComment(props.postId, pendingDeleteId.value)
  pendingDeleteId.value = null
}
</script>

<template>
  <div class="mt-4 space-y-3 border-t border-cyber-border pt-3">
    <div v-for="comment in topLevel" :key="comment.id" class="space-y-2">
      <div class="relative flex items-start justify-between gap-2 rounded-hud border border-cyber-border bg-cyber-surface/40 p-3 backdrop-blur-md has-[.popover-panel]:z-20">
        <div class="flex-1">
          <p class="text-xs font-bold tracking-wider text-cyber-text">// {{ comment.author.name }}</p>
          <p class="mt-1 border-l border-cyber-neon-indigo pl-2 font-mono text-xs leading-relaxed text-cyber-text/90">
            <LinkifiedText :text="comment.body" :hashtags="comment.hashtags" :mentions="comment.mentions" />
          </p>
          <div class="mt-2 flex items-center gap-3 font-mono text-[9px] uppercase tracking-widest text-cyber-muted">
            <span>{{ useRelativeTime(comment.created_at) }}</span>
            <ReactionButton
              :count="comment.likes_count"
              :my-reaction="comment.my_reaction"
              variant="inline"
              @react="(type) => onReact(comment.id, type)"
              @unreact="onUnreact(comment.id)"
            />
            <button type="button" class="hover:text-cyber-neon-cyan" @click="replyingTo = comment.id">{{ t('feed.commentThread.reply') }}</button>
          </div>
        </div>
        <button
          v-if="canDelete(comment)"
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass p-1 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
          :aria-label="t('feed.commentThread.deleteComment')"
          @click="pendingDeleteId = comment.id"
        >
          <Trash2 class="h-3 w-3" />
        </button>
      </div>

      <div
        v-for="reply in repliesFor(comment.id)"
        :key="reply.id"
        class="relative ml-6 flex items-start justify-between gap-2 rounded-hud border border-cyber-border bg-cyber-surface/40 p-3 backdrop-blur-md has-[.popover-panel]:z-20"
      >
        <div class="flex-1">
          <p class="text-xs font-bold tracking-wider text-cyber-text">// {{ reply.author.name }}</p>
          <p class="mt-1 border-l border-cyber-neon-indigo pl-2 font-mono text-xs leading-relaxed text-cyber-text/90">
            <LinkifiedText :text="reply.body" :hashtags="reply.hashtags" :mentions="reply.mentions" />
          </p>
          <div class="mt-2 flex items-center gap-3 font-mono text-[9px] uppercase tracking-widest text-cyber-muted">
            <span>{{ useRelativeTime(reply.created_at) }}</span>
            <ReactionButton
              :count="reply.likes_count"
              :my-reaction="reply.my_reaction"
              variant="inline"
              @react="(type) => onReact(reply.id, type)"
              @unreact="onUnreact(reply.id)"
            />
          </div>
        </div>
        <button
          v-if="canDelete(reply)"
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass p-1 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
          :aria-label="t('feed.commentThread.deleteReply')"
          @click="pendingDeleteId = reply.id"
        >
          <Trash2 class="h-3 w-3" />
        </button>
      </div>

      <form
        v-if="replyingTo === comment.id"
        class="relative ml-6 flex gap-2 has-[.popover-panel]:z-20"
        @submit.prevent="submitComment(comment.id)"
      >
        <input
          v-model="newComment"
          type="text"
          :placeholder="t('feed.commentThread.replyPlaceholder')"
          class="flex-1 rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
        />

        <div class="relative">
          <button
            type="button"
            class="rounded-full p-1.5 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
            :aria-label="t('feed.commentThread.mention')"
            @click="toggleMentionPicker"
          >
            <UserPlus class="h-4 w-4" />
          </button>

          <div v-if="mention.showPicker.value" class="fixed inset-0 z-0" @click="mention.close()" />

          <div
            v-if="mention.showPicker.value"
            class="popover-panel absolute right-0 z-10 mt-2 w-64 rounded-hud border border-cyber-border bg-cyber-glass p-3 backdrop-blur-md"
            @click.stop
          >
            <input
              v-model="mention.query.value"
              type="text"
              :placeholder="t('feed.postComposer.mentionSearchPlaceholder')"
              class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              @input="mention.onSearchInput()"
            />

            <ul v-if="mention.results.value.length" class="mt-2 max-h-48 space-y-1 overflow-y-auto">
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
            <p v-else-if="mention.searching.value" class="mt-2 font-mono text-[10px] text-cyber-muted">
              {{ t('common.loading') }}
            </p>
            <p
              v-else-if="mention.query.value.trim().length >= 2"
              class="mt-2 font-mono text-[10px] text-cyber-muted"
            >
              {{ t('feed.postComposer.mentionNoResults') }}
            </p>
          </div>
        </div>

        <AppButton type="submit" :label="t('feed.commentThread.submitReply')" :loading="submitting" />
        <button
          type="button"
          class="font-mono text-xs text-cyber-muted transition-colors duration-300 hover:text-cyber-text"
          @click="replyingTo = null"
        >
          {{ t('common.cancel') }}
        </button>
      </form>
    </div>

    <form v-if="replyingTo === null" class="relative flex gap-2 has-[.popover-panel]:z-20" @submit.prevent="submitComment()">
      <input
        v-model="newComment"
        type="text"
        :placeholder="t('feed.commentThread.commentPlaceholder')"
        class="flex-1 rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
      />

      <div class="relative">
        <button
          type="button"
          class="rounded-full p-1.5 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
          :aria-label="t('feed.commentThread.mention')"
          @click="toggleMentionPicker"
        >
          <UserPlus class="h-4 w-4" />
        </button>

        <div v-if="mention.showPicker.value" class="fixed inset-0 z-0" @click="mention.close()" />

        <div
          v-if="mention.showPicker.value"
          class="popover-panel absolute right-0 z-10 mt-2 w-64 rounded-hud border border-cyber-border bg-cyber-glass p-3 backdrop-blur-md"
          @click.stop
        >
          <input
            v-model="mention.query.value"
            type="text"
            :placeholder="t('feed.postComposer.mentionSearchPlaceholder')"
            class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
            @input="mention.onSearchInput()"
          />

          <ul v-if="mention.results.value.length" class="mt-2 max-h-48 space-y-1 overflow-y-auto">
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
          <p v-else-if="mention.searching.value" class="mt-2 font-mono text-[10px] text-cyber-muted">
            {{ t('common.loading') }}
          </p>
          <p
            v-else-if="mention.query.value.trim().length >= 2"
            class="mt-2 font-mono text-[10px] text-cyber-muted"
          >
            {{ t('feed.postComposer.mentionNoResults') }}
          </p>
        </div>
      </div>

      <AppButton type="submit" :label="t('feed.commentThread.submitComment')" :loading="submitting" />
    </form>

    <ConfirmDialog
      :open="pendingDeleteId !== null"
      :title="t('feed.commentThread.confirmDeleteTitle')"
      :message="t('feed.commentThread.confirmDeleteMessage')"
      @confirm="onConfirmDelete"
      @cancel="pendingDeleteId = null"
    />
  </div>
</template>
