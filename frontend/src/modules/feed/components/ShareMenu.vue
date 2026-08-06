<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Copy, Globe, Repeat2, Share2 } from '@lucide/vue'
import RepostDialog from './RepostDialog.vue'
import SocialShareDialog from './SocialShareDialog.vue'
import { copyToClipboard, postPermalink } from '../utils/shareIntents'
import type { Post } from '../types'

const props = defineProps<{ post: Post }>()
const { t } = useI18n()

const open = ref(false)
const showRepostDialog = ref(false)
const showSocialDialog = ref(false)
const copied = ref(false)
const copyError = ref(false)

const repostTarget = computed(() => props.post.shared_post ?? props.post)

function toggle(): void {
  open.value = !open.value
}

function openRepostDialog(): void {
  open.value = false
  showRepostDialog.value = true
}

function openSocialDialog(): void {
  open.value = false
  showSocialDialog.value = true
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
      class="inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-glass px-2.5 py-1 font-mono text-xs text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-indigo/40 hover:text-cyber-neon-indigo"
      @click="toggle"
    >
      <Share2 class="h-3 w-3" />
      {{ post.shares_count }}
    </button>

    <div v-if="open" class="fixed inset-0 z-0" @click="open = false" />

    <div
      v-if="open"
      class="popover-panel absolute right-0 z-10 mt-2 w-56 rounded-hud border border-cyber-border bg-cyber-surface py-1"
      @click.stop
    >
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left font-mono text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
        @click="openRepostDialog"
      >
        <Repeat2 class="h-4 w-4" /> {{ t('feed.shareMenu.shareToProfile') }}
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left font-mono text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
        @click="openSocialDialog"
      >
        <Globe class="h-4 w-4" /> {{ t('feed.shareMenu.shareToSocial') }}
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-2 px-3 py-2 text-left font-mono text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
        @click="onCopyLink"
      >
        <Copy class="h-4 w-4" />
        {{
          copyError
            ? t('feed.shareMenu.copyError')
            : copied
              ? t('feed.shareMenu.copied')
              : t('feed.shareMenu.copyLink')
        }}
      </button>
    </div>

    <RepostDialog v-model:open="showRepostDialog" :post="repostTarget" />
    <SocialShareDialog v-model:open="showSocialDialog" :post="post" />
  </div>
</template>
