<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { ExternalLink } from '@lucide/vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
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
const { t } = useI18n()

function close(): void {
  emit('update:open', false)
}

function share(platform: SocialPlatform): void {
  openShareWindow(platform.buildUrl(postPermalink(props.post.id)))
  close()
}
</script>

<template>
  <AppModal :open="open" size="sm" @close="close">
 <h2 class="text-xs font-bold text-cyber-text">
      {{ t('feed.socialShareDialog.title') }}
    </h2>

    <div class="mt-4 flex items-center justify-center gap-6">
      <button
        v-for="platform in PLATFORMS"
        :key="platform.name"
        type="button"
 class="inline-flex flex-col items-center gap-1.5 text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
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
 class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
        @click="close"
      >
        {{ t('common.cancel') }}
      </button>
    </div>
  </AppModal>
</template>
