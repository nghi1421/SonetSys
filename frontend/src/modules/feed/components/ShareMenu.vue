<script setup lang="ts">
import { computed, ref } from 'vue'
import { Copy, ExternalLink, Repeat2, Share2 } from '@lucide/vue'
import RepostDialog from './RepostDialog.vue'
import {
  copyToClipboard,
  facebookShareUrl,
  linkedInShareUrl,
  openShareWindow,
  postPermalink,
  threadsShareUrl,
} from '../utils/shareIntents'
import type { Post } from '../types'

const props = defineProps<{ post: Post }>()

const open = ref(false)
const showRepostDialog = ref(false)
const copied = ref(false)
const copyError = ref(false)

// Reposting an already-shared post should preview the real content, not an
// empty reshare wrapper — the backend flattens shared_post_id either way.
const repostTarget = computed(() => props.post.shared_post ?? props.post)

function toggle(): void {
  open.value = !open.value
}

function openRepostDialog(): void {
  open.value = false
  showRepostDialog.value = true
}

function shareToFacebook(): void {
  open.value = false
  openShareWindow(facebookShareUrl(postPermalink(props.post.id)))
}

function shareToLinkedIn(): void {
  open.value = false
  openShareWindow(linkedInShareUrl(postPermalink(props.post.id)))
}

function shareToThreads(): void {
  open.value = false
  openShareWindow(threadsShareUrl(postPermalink(props.post.id)))
}

async function onCopyLink(): Promise<void> {
  const succeeded = await copyToClipboard(postPermalink(props.post.id))
  copied.value = succeeded
  copyError.value = !succeeded

  setTimeout(() => {
    copied.value = false
    copyError.value = false
    open.value = false
  }, 1200)
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="flex items-center gap-1.5 rounded-md px-2 py-1 text-sm text-slate-500 transition-colors duration-200 hover:text-accent-600 dark:text-zinc-400"
      @click="toggle"
    >
      <Share2 class="h-4 w-4" />
      {{ post.shares_count }}
    </button>

    <div v-if="open" class="fixed inset-0 z-0" @click="open = false" />

    <div
      v-if="open"
      class="absolute left-0 z-10 mt-2 w-56 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
      @click.stop
    >
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 transition-colors duration-200 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
        @click="openRepostDialog"
      >
        <Repeat2 class="h-4 w-4" /> Share to Profile
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 transition-colors duration-200 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
        @click="shareToFacebook"
      >
        <ExternalLink class="h-4 w-4" /> Share to Facebook
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 transition-colors duration-200 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
        @click="shareToLinkedIn"
      >
        <ExternalLink class="h-4 w-4" /> Share to LinkedIn
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 transition-colors duration-200 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
        @click="shareToThreads"
      >
        <ExternalLink class="h-4 w-4" /> Share to Threads
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 transition-colors duration-200 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
        @click="onCopyLink"
      >
        <Copy class="h-4 w-4" />
        {{ copyError ? 'Could not copy' : copied ? 'Link copied!' : 'Copy link' }}
      </button>
    </div>

    <RepostDialog v-model:open="showRepostDialog" :post="repostTarget" />
  </div>
</template>
