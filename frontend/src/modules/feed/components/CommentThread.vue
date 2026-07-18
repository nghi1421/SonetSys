<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Trash2 } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppButton from '@/shared/components/ui/AppButton.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import ReactionButton from './ReactionButton.vue'
import { useFeedStore } from '../store/feedStore'
import type { Comment, ReactionType } from '../types'

const props = defineProps<{ postId: number }>()

const feedStore = useFeedStore()
const authStore = useAuthStore()
const { t } = useI18n()

const newComment = ref('')
const replyingTo = ref<number | null>(null)
const submitting = ref(false)
const pendingDeleteId = ref<number | null>(null)

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
    await feedStore.createComment(props.postId, newComment.value.trim(), parentId)
    newComment.value = ''
    replyingTo.value = null
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
            {{ comment.body }}
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
            {{ reply.body }}
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

      <form v-if="replyingTo === comment.id" class="ml-6 flex gap-2" @submit.prevent="submitComment(comment.id)">
        <input
          v-model="newComment"
          type="text"
          :placeholder="t('feed.commentThread.replyPlaceholder')"
          class="flex-1 rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
        />
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

    <form v-if="replyingTo === null" class="flex gap-2" @submit.prevent="submitComment()">
      <input
        v-model="newComment"
        type="text"
        :placeholder="t('feed.commentThread.commentPlaceholder')"
        class="flex-1 rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
      />
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
