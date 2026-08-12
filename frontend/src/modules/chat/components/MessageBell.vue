<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { MessageCircle } from '@lucide/vue'
import { useChatStore } from '../store/chatStore'

const chatStore = useChatStore()
const router = useRouter()
const { t } = useI18n()

onMounted(() => {
  chatStore.fetchUnreadCount()
})

function goToMessages(): void {
  router.push({ name: 'messages' })
}
</script>

<template>
  <button
    type="button"
    class="relative rounded-full border border-cyber-border bg-cyber-glass p-2 text-cyber-muted backdrop-blur-none transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg"
    :aria-label="t('chat.bell.ariaLabel')"
    @click="goToMessages"
  >
    <MessageCircle class="h-4 w-4" />
    <span
      v-if="chatStore.unreadCount > 0"
      class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-cyber-neon-pink px-1 font-mono text-xs font-bold text-white shadow-pink-glow"
    >
      {{ chatStore.unreadCount > 9 ? '9+' : chatStore.unreadCount }}
    </span>
  </button>
</template>
