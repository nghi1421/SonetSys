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
  { name: 'Threads', bg: 'bg-cyber-surface', buildUrl: threadsShareUrl },
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
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-cyber-bg/80 px-4 backdrop-blur-sm"
      @click="close"
    >
      <div class="w-full max-w-sm rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md" @click.stop>
        <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">Share to social media</h2>

        <div class="mt-4 flex items-center justify-center gap-6">
          <button
            v-for="platform in PLATFORMS"
            :key="platform.name"
            type="button"
            class="inline-flex flex-col items-center gap-1.5 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
            @click="share(platform)"
          >
            <span
              class="inline-flex h-12 w-12 items-center justify-center rounded-full text-white shadow-cyan-glow"
              :class="platform.bg"
            >
              <ExternalLink class="h-5 w-5" />
            </span>
            {{ platform.name }}
          </button>
        </div>

        <div class="mt-4 flex justify-end">
          <button
            type="button"
            class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
            @click="close"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
