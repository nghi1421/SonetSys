<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { MessageCircle } from '@lucide/vue'
import { useChatStore } from '../store/chatStore'

const chatStore = useChatStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

// Hidden on the messages pages themselves — clicking it there would just
// re-navigate to where the user already is, and on a conversation thread
// it would float over the message composer.
const isOnMessagesRoute = computed(() => route.name === 'messages' || route.name === 'conversation')

onMounted(() => {
  chatStore.fetchUnreadCount()
})

function goToMessages(): void {
  router.push({ name: 'messages' })
}
</script>

<template>
  <button
    v-if="!isOnMessagesRoute"
    type="button"
    class="fixed bottom-6 right-6 z-30 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink text-white shadow-lg transition-all duration-300 hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg"
    :aria-label="t('chat.bell.ariaLabel')"
    @click="goToMessages"
  >
    <MessageCircle class="h-6 w-6" />
    <span
      v-if="chatStore.unreadCount > 0"
      class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-cyber-neon-pink px-1 font-mono text-xs font-bold text-white shadow-pink-glow"
    >
      {{ chatStore.unreadCount > 9 ? '9+' : chatStore.unreadCount }}
    </span>
  </button>
</template>
