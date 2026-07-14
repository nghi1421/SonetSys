<script setup lang="ts">
import { computed, ref } from 'vue'
import { Heart, Trash2 } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppButton from '@/shared/components/ui/AppButton.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useFeedStore } from '../store/feedStore'
import type { Comment } from '../types'

const props = defineProps<{ postId: number }>()

const feedStore = useFeedStore()
const authStore = useAuthStore()

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
  return authStore.user?.id === comment.author.id || authStore.user?.role.slug === 'tenant-admin'
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

async function onToggleLike(commentId: number): Promise<void> {
  await feedStore.toggleCommentLike(props.postId, commentId)
}

async function onConfirmDelete(): Promise<void> {
  if (pendingDeleteId.value === null) return
  await feedStore.deleteComment(props.postId, pendingDeleteId.value)
  pendingDeleteId.value = null
}
</script>

<template>
  <div class="mt-4 space-y-3 border-t border-zinc-100 pt-3 dark:border-zinc-800">
    <div v-for="comment in topLevel" :key="comment.id" class="space-y-2">
      <div class="flex items-start justify-between gap-2">
        <div class="flex-1 rounded-md bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
          <p class="text-sm font-medium text-slate-900 dark:text-zinc-100">{{ comment.author.name }}</p>
          <p class="text-sm text-slate-700 dark:text-zinc-300">{{ comment.body }}</p>
          <div class="mt-1 flex items-center gap-3 text-xs text-slate-500 dark:text-zinc-400">
            <span>{{ useRelativeTime(comment.created_at) }}</span>
            <button
              type="button"
              class="flex items-center gap-1 transition-colors duration-200"
              :class="comment.liked_by_me ? 'text-red-600' : 'hover:text-red-600'"
              @click="onToggleLike(comment.id)"
            >
              <Heart class="h-3 w-3" :fill="comment.liked_by_me ? 'currentColor' : 'none'" />
              {{ comment.likes_count }}
            </button>
            <button type="button" class="hover:text-accent-600" @click="replyingTo = comment.id">Reply</button>
          </div>
        </div>
        <button
          v-if="canDelete(comment)"
          type="button"
          class="rounded p-1 text-slate-400 transition-colors duration-200 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950"
          aria-label="Delete comment"
          @click="pendingDeleteId = comment.id"
        >
          <Trash2 class="h-3.5 w-3.5" />
        </button>
      </div>

      <div v-for="reply in repliesFor(comment.id)" :key="reply.id" class="ml-6 flex items-start justify-between gap-2">
        <div class="flex-1 rounded-md bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
          <p class="text-sm font-medium text-slate-900 dark:text-zinc-100">{{ reply.author.name }}</p>
          <p class="text-sm text-slate-700 dark:text-zinc-300">{{ reply.body }}</p>
          <div class="mt-1 flex items-center gap-3 text-xs text-slate-500 dark:text-zinc-400">
            <span>{{ useRelativeTime(reply.created_at) }}</span>
            <button
              type="button"
              class="flex items-center gap-1 transition-colors duration-200"
              :class="reply.liked_by_me ? 'text-red-600' : 'hover:text-red-600'"
              @click="onToggleLike(reply.id)"
            >
              <Heart class="h-3 w-3" :fill="reply.liked_by_me ? 'currentColor' : 'none'" />
              {{ reply.likes_count }}
            </button>
          </div>
        </div>
        <button
          v-if="canDelete(reply)"
          type="button"
          class="rounded p-1 text-slate-400 transition-colors duration-200 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950"
          aria-label="Delete reply"
          @click="pendingDeleteId = reply.id"
        >
          <Trash2 class="h-3.5 w-3.5" />
        </button>
      </div>

      <form v-if="replyingTo === comment.id" class="ml-6 flex gap-2" @submit.prevent="submitComment(comment.id)">
        <input
          v-model="newComment"
          type="text"
          placeholder="Write a reply…"
          class="flex-1 rounded-md border border-zinc-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
        />
        <AppButton type="submit" label="Reply" :loading="submitting" />
        <button
          type="button"
          class="text-sm text-slate-500 hover:text-slate-700 dark:text-zinc-400"
          @click="replyingTo = null"
        >
          Cancel
        </button>
      </form>
    </div>

    <form v-if="replyingTo === null" class="flex gap-2" @submit.prevent="submitComment()">
      <input
        v-model="newComment"
        type="text"
        placeholder="Write a comment…"
        class="flex-1 rounded-md border border-zinc-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
      />
      <AppButton type="submit" label="Comment" :loading="submitting" />
    </form>

    <ConfirmDialog
      :open="pendingDeleteId !== null"
      title="Delete comment?"
      message="This can't be undone."
      @confirm="onConfirmDelete"
      @cancel="pendingDeleteId = null"
    />
  </div>
</template>
