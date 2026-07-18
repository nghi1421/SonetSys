<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { MessageCircle } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useChatStore } from '../store/chatStore'

const chatStore = useChatStore()
const { t } = useI18n()

function initialOf(name: string): string {
  return name.trim().charAt(0).toUpperCase()
}

onMounted(() => {
  chatStore.fetchConversations()
})
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// {{ t('chat.messagesListView.title') }}</h1>

      <div v-if="chatStore.conversationsLoading" class="space-y-3">
        <div
          v-for="i in 3"
          :key="i"
          class="flex animate-pulse items-center gap-3 rounded-hud border border-cyber-border bg-cyber-surface/60 p-4 backdrop-blur-md"
        >
          <div class="h-10 w-10 rounded-full bg-cyber-border" />
          <div class="flex-1 space-y-2">
            <div class="h-2.5 w-1/3 rounded-full bg-cyber-border" />
            <div class="h-2 w-2/3 rounded-full bg-cyber-border" />
          </div>
        </div>
      </div>

      <div v-else-if="chatStore.conversations.length === 0" class="flex flex-col items-center py-16 text-center">
        <MessageCircle class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('chat.messagesListView.emptyTitle') }}</p>
        <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('chat.messagesListView.emptyDescription') }}</p>
      </div>

      <ul v-else class="space-y-3">
        <li v-for="conversation in chatStore.conversations" :key="conversation.id">
          <router-link
            :to="{ name: 'conversation', params: { conversationId: conversation.id } }"
            class="flex items-center gap-3 rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/40 hover:shadow-cyan-glow"
          >
            <img
              v-if="conversation.other_participant.avatar_url"
              :src="conversation.other_participant.avatar_url"
              :alt="conversation.other_participant.name"
              class="h-10 w-10 shrink-0 rounded-full object-cover"
            />
            <span
              v-else
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface font-mono text-sm font-bold text-cyber-text"
            >
              {{ initialOf(conversation.other_participant.name) }}
            </span>

            <div class="min-w-0 flex-1">
              <p class="truncate text-xs font-bold text-cyber-text">{{ conversation.other_participant.name }}</p>
              <p v-if="conversation.last_message_at" class="mt-0.5 font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
                {{ useRelativeTime(conversation.last_message_at) }}
              </p>
            </div>

            <span
              v-if="conversation.is_unread"
              class="h-2.5 w-2.5 shrink-0 rounded-full bg-cyber-neon-pink shadow-pink-glow"
              :aria-label="t('chat.messagesListView.unreadIndicator')"
            />
          </router-link>
        </li>
      </ul>
    </div>
  </AppShell>
</template>
