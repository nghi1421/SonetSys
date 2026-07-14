<script setup lang="ts">
import { computed, ref } from 'vue'
import { Heart, Link2, MessageCircle, Pencil, Trash2 } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppButton from '@/shared/components/ui/AppButton.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import CommentThread from './CommentThread.vue'
import { useFeedStore } from '../store/feedStore'
import type { Post } from '../types'

const props = defineProps<{ post: Post }>()

const feedStore = useFeedStore()
const authStore = useAuthStore()

const showComments = ref(false)
const copied = ref(false)
const shareError = ref(false)
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

async function copyToClipboard(text: string): Promise<boolean> {
  if (navigator.clipboard) {
    try {
      await navigator.clipboard.writeText(text)
      return true
    } catch {
      // Fall through to the legacy fallback below.
    }
  }

  const textarea = document.createElement('textarea')
  textarea.value = text
  textarea.style.position = 'fixed'
  textarea.style.opacity = '0'
  document.body.appendChild(textarea)
  textarea.select()

  let succeeded = false
  try {
    succeeded = document.execCommand('copy')
  } catch {
    succeeded = false
  } finally {
    document.body.removeChild(textarea)
  }

  return succeeded
}

async function onShare(): Promise<void> {
  const url = `${window.location.origin}/posts/${props.post.id}`
  const succeeded = await copyToClipboard(url)

  copied.value = succeeded
  shareError.value = !succeeded

  setTimeout(() => {
    copied.value = false
    shareError.value = false
  }, 2000)
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
  <article
    class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
  >
    <header class="flex items-start justify-between">
      <div>
        <h4 class="text-xs font-bold tracking-wider text-cyber-text">// {{ post.author.name }}</h4>
        <div class="mt-1 flex items-center gap-2">
          <span class="font-mono text-[9px] uppercase tracking-widest text-cyber-muted">
            {{ useRelativeTime(post.created_at) }}
          </span>
          <span class="rounded border border-cyber-border bg-cyber-surface px-2 py-0.5 font-mono text-[9px] text-cyber-muted">
            POST-{{ post.id }}
          </span>
        </div>
      </div>
      <div v-if="isOwner || canDelete" class="flex items-center gap-2">
        <button
          v-if="isOwner && !editing"
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
          aria-label="Edit post"
          @click="startEditing"
        >
          <Pencil class="h-3.5 w-3.5" />
        </button>
        <button
          v-if="canDelete"
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
          aria-label="Delete post"
          @click="confirmingDelete = true"
        >
          <Trash2 class="h-3.5 w-3.5" />
        </button>
      </div>
    </header>

    <div v-if="editing" class="mt-3 space-y-2">
      <textarea
        v-model="editBody"
        rows="3"
        class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
      />
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass px-3 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          @click="editing = false"
        >
          Cancel
        </button>
        <AppButton label="Save" @click="saveEdit" />
      </div>
    </div>
    <p v-else class="mt-3 whitespace-pre-wrap border-l border-cyber-neon-indigo pl-2 font-mono text-xs leading-relaxed text-cyber-text/90">
      {{ post.body }}
    </p>

    <footer class="mt-4 flex items-center gap-2 border-t border-cyber-border pt-3">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 font-mono text-[10px] transition-all duration-300"
        :class="
          post.liked_by_me
            ? 'border-cyber-neon-pink/40 bg-cyber-neon-pink/10 text-cyber-neon-pink shadow-pink-glow'
            : 'border-cyber-border bg-cyber-glass text-cyber-muted hover:border-cyber-neon-pink/40 hover:text-cyber-neon-pink'
        "
        @click="onToggleLike"
      >
        <Heart class="h-3 w-3" :fill="post.liked_by_me ? 'currentColor' : 'none'" />
        {{ post.likes_count }}
      </button>

      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-glass px-2.5 py-1 font-mono text-[10px] text-cyber-muted transition-all duration-300 hover:border-cyber-neon-cyan/40 hover:text-cyber-neon-cyan"
        @click="onToggleComments"
      >
        <MessageCircle class="h-3 w-3" />
        {{ post.comments_count }}
      </button>

      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-glass px-2.5 py-1 font-mono text-[10px] text-cyber-muted transition-all duration-300 hover:border-cyber-neon-indigo/40 hover:text-cyber-neon-indigo"
        @click="onShare"
      >
        <Link2 class="h-3 w-3" />
        {{ shareError ? 'Could not copy' : copied ? 'Copied!' : 'Share' }}
      </button>
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
