<script setup lang="ts">
import { ExternalLink } from '@lucide/vue'
import {
  facebookShareUrl,
  linkedInShareUrl,
  openShareWindow,
  postPermalink,
  threadsShareUrl,
} from '../utils/shareIntents'
import type { Post } from '../types'

interface SocialPlatform {
  name: string
  bg: string
  buildUrl: (url: string) => string
}

const PLATFORMS: SocialPlatform[] = [
  { name: 'Facebook', bg: 'bg-blue-600', buildUrl: facebookShareUrl },
  { name: 'LinkedIn', bg: 'bg-blue-800', buildUrl: linkedInShareUrl },
  { name: 'Threads', bg: 'bg-zinc-900 dark:bg-zinc-700', buildUrl: threadsShareUrl },
]

const props = defineProps<{ open: boolean; post: Post }>()
const emit = defineEmits<{ 'update:open': [boolean] }>()

function close(): void {
  emit('update:open', false)
}

function share(platform: SocialPlatform): void {
  openShareWindow(platform.buildUrl(postPermalink(props.post.id)))
  close()
}
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click="close">
    <div class="w-full max-w-sm rounded-lg bg-white p-5 shadow-lg dark:bg-zinc-900" @click.stop>
      <h2 class="text-base font-semibold text-slate-900 dark:text-zinc-100">Share to social media</h2>

      <div class="mt-4 flex items-center justify-center gap-6">
        <button
          v-for="platform in PLATFORMS"
          :key="platform.name"
          type="button"
          class="inline-flex flex-col items-center gap-1.5 text-sm text-slate-600 transition-colors duration-200 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-zinc-100"
          @click="share(platform)"
        >
          <span class="inline-flex h-12 w-12 items-center justify-center rounded-full text-white" :class="platform.bg">
            <ExternalLink class="h-5 w-5" />
          </span>
          {{ platform.name }}
        </button>
      </div>

      <div class="mt-4 flex justify-end">
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-sm text-slate-600 transition-colors duration-200 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
          @click="close"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>
