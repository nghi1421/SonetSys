<script setup lang="ts">
import { computed, ref } from 'vue'
import { Heart, MessageCircle, Pencil, Trash2 } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import CommentThread from './CommentThread.vue'
import ShareMenu from './ShareMenu.vue'
import SharedPostPreview from './SharedPostPreview.vue'
import { useFeedStore } from '../store/feedStore'
import type { Post } from '../types'

const props = defineProps<{ post: Post }>()

const feedStore = useFeedStore()
const authStore = useAuthStore()

const showComments = ref(false)
const editing = ref(false)
const editBody = ref(props.post.body)
const confirmingDelete = ref(false)

const isOwner = computed(() => authStore.user?.id === props.post.author.id)
const canModerate = computed(() => authStore.user?.role.slug === 'tenant-admin')
const canDelete = computed(() => isOwner.value || canModerate.value)

async function onToggleLike(): Promise<void> {
  await feedStore.toggleLike(props.post.id)
}

async function onToggleComments(): Promise<void> {
  showComments.value = !showComments.value
  if (showComments.value && !feedStore.commentsByPost[props.post.id]) {
    await feedStore.fetchComments(props.post.id)
  }
}

async function onConfirmDelete(): Promise<void> {
  confirmingDelete.value = false
  await feedStore.deletePost(props.post.id)
}

function startEditing(): void {
  editBody.value = props.post.body
  editing.value = true
}

async function saveEdit(): Promise<void> {
  if (!editBody.value.trim()) return
  await feedStore.updatePost(props.post.id, { body: editBody.value.trim() })
  editing.value = false
}
</script>

<template>
  <article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
    <header class="flex items-start justify-between">
      <div>
        <p class="text-sm font-medium text-slate-900 dark:text-zinc-100">{{ post.author.name }}</p>
        <p class="text-xs text-slate-500 dark:text-zinc-400">{{ useRelativeTime(post.created_at) }}</p>
      </div>
      <div v-if="isOwner || canDelete" class="flex items-center gap-1">
        <button
          v-if="isOwner && !editing"
          type="button"
          class="rounded p-1.5 text-slate-400 transition-colors duration-200 hover:bg-zinc-100 hover:text-slate-600 dark:hover:bg-zinc-800"
          aria-label="Edit post"
          @click="startEditing"
        >
          <Pencil class="h-4 w-4" />
        </button>
        <button
          v-if="canDelete"
          type="button"
          class="rounded p-1.5 text-slate-400 transition-colors duration-200 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950"
          aria-label="Delete post"
          @click="confirmingDelete = true"
        >
          <Trash2 class="h-4 w-4" />
        </button>
      </div>
    </header>

    <div v-if="editing" class="mt-3 space-y-2">
      <textarea
        v-model="editBody"
        rows="3"
        class="block w-full resize-none rounded-md border border-zinc-200 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-accent-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
      />
      <div class="flex justify-end gap-2">
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-sm text-slate-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
          @click="editing = false"
        >
          Cancel
        </button>
        <button
          type="button"
          class="rounded-md bg-accent-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-accent-700"
          @click="saveEdit"
        >
          Save
        </button>
      </div>
    </div>
    <template v-else>
      <p v-if="post.body" class="mt-3 whitespace-pre-wrap text-sm text-slate-700 dark:text-zinc-300">{{ post.body }}</p>
      <SharedPostPreview v-if="post.shared_post" :post="post.shared_post" class="mt-3" />
    </template>

    <footer class="mt-4 flex items-center gap-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
      <button
        type="button"
        class="flex items-center gap-1.5 rounded-md px-2 py-1 text-sm transition-colors duration-200"
        :class="post.liked_by_me ? 'text-red-600' : 'text-slate-500 hover:text-red-600 dark:text-zinc-400'"
        @click="onToggleLike"
      >
        <Heart class="h-4 w-4" :fill="post.liked_by_me ? 'currentColor' : 'none'" />
        {{ post.likes_count }}
      </button>

      <button
        type="button"
        class="flex items-center gap-1.5 rounded-md px-2 py-1 text-sm text-slate-500 transition-colors duration-200 hover:text-accent-600 dark:text-zinc-400"
        @click="onToggleComments"
      >
        <MessageCircle class="h-4 w-4" />
        {{ post.comments_count }}
      </button>

      <ShareMenu :post="post" />
    </footer>

    <CommentThread v-if="showComments" :post-id="post.id" />

    <ConfirmDialog
      :open="confirmingDelete"
      title="Delete post?"
      message="This can't be undone."
      @confirm="onConfirmDelete"
      @cancel="confirmingDelete = false"
    />
  </article>
</template>
