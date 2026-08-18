<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, MessageCircle } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useChatStore } from '../store/chatStore'

const route = useRoute()
const chatStore = useChatStore()
const authStore = useAuthStore()
const { t } = useI18n()

const conversationId = computed(() => Number(route.params.conversationId))
const body = ref('')
const sending = ref(false)
const error = ref<string | null>(null)
const scrollContainer = ref<HTMLElement | null>(null)

const conversation = computed(() => chatStore.conversations.find((item) => item.id === conversationId.value) ?? null)
const messages = computed(() => chatStore.messagesByConversation[conversationId.value] ?? [])
const nextCursor = computed(() => chatStore.nextCursorByConversation[conversationId.value] ?? null)
const lastMessageId = computed(() => messages.value[messages.value.length - 1]?.id ?? null)

function initialOf(name: string): string {
  return name.trim().charAt(0).toUpperCase()
}

function isOwnMessage(senderId: number): boolean {
  return senderId === authStore.user?.id
}

async function scrollToBottom(): Promise<void> {
  await nextTick()
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
  }
}

async function load(): Promise<void> {
  chatStore.setActiveConversation(conversationId.value)
  if (!conversation.value) {
    await chatStore.fetchConversations()
  }
  await chatStore.fetchMessages(conversationId.value)
  await scrollToBottom()
}

async function handleSend(): Promise<void> {
  const trimmed = body.value.trim()
  if (!trimmed || sending.value) return

  sending.value = true
  error.value = null
  try {
    await chatStore.sendMessage(conversationId.value, trimmed)
    body.value = ''
  } catch {
    error.value = t('chat.conversationView.sendError')
  } finally {
    sending.value = false
  }
}

function loadOlder(): void {
  chatStore.fetchMoreMessages(conversationId.value)
}

onMounted(load)
watch(conversationId, load)
// Only auto-scroll when a message is appended at the end (new send/push),
// never when older messages are prepended by the "load older" button.
watch(lastMessageId, (current, previous) => {
  if (current !== null && current !== previous) {
    scrollToBottom()
  }
})

onBeforeUnmount(() => {
  chatStore.setActiveConversation(null)
})
</script>

<template>
  <AppShell>
    <div class="mx-auto flex h-[calc(100vh-7rem)] max-w-3xl flex-col">
      <div class="flex items-center gap-3 border-b border-cyber-border pb-4">
        <router-link
          :to="{ name: 'messages' }"
          class="rounded-full p-2 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
          :aria-label="t('chat.conversationView.back')"
        >
          <ArrowLeft class="h-4 w-4" />
        </router-link>
        <template v-if="conversation">
          <img
            v-if="conversation.other_participant.avatar_url"
            :src="conversation.other_participant.avatar_url"
            :alt="conversation.other_participant.name"
            class="h-8 w-8 rounded-full object-cover"
          />
          <span
            v-else
 class="flex h-8 w-8 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface text-xs font-bold text-cyber-text"
          >
            {{ initialOf(conversation.other_participant.name) }}
          </span>
          <p class="text-xs font-bold text-cyber-text">{{ conversation.other_participant.name }}</p>
        </template>
      </div>

      <AppAlert v-if="error" class="mt-4">{{ error }}</AppAlert>

      <div ref="scrollContainer" class="flex-1 space-y-3 overflow-y-auto py-4">
        <div v-if="chatStore.messagesLoading" class="space-y-3">
          <div
            v-for="i in 3"
            :key="i"
            class="h-10 w-2/3 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 backdrop-blur-md"
          />
        </div>

        <template v-else>
          <div v-if="nextCursor" class="flex justify-center pb-2">
            <AppButton
              :label="t('chat.conversationView.loadOlder')"
              variant="secondary"
              :loading="chatStore.messagesLoadingMore"
              @click="loadOlder"
            />
          </div>

          <div v-if="messages.length === 0" class="flex flex-col items-center py-16 text-center">
            <MessageCircle class="h-8 w-8 text-cyber-muted" />
            <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('chat.conversationView.emptyTitle') }}</p>
 <p class="mt-1 text-xs text-cyber-muted">{{ t('chat.conversationView.emptyDescription') }}</p>
          </div>

          <div
            v-for="message in messages"
            :key="message.id"
            class="max-w-[75%] rounded-hud border px-3 py-2 backdrop-blur-md"
            :class="
              isOwnMessage(message.sender_id)
                ? 'ml-auto border-cyber-neon-cyan/30 bg-gradient-to-r from-cyber-neon-cyan/10 to-cyber-neon-indigo/10'
                : 'mr-auto border-cyber-border bg-cyber-glass'
            "
          >
 <p class="whitespace-pre-wrap break-words text-xs text-cyber-text">{{ message.body }}</p>
 <p class="mt-1 text-xs text-cyber-muted">
              {{ useRelativeTime(message.created_at) }}
            </p>
          </div>
        </template>
      </div>

      <form
        class="flex items-end gap-2 rounded-hud border border-cyber-border bg-cyber-glass p-3 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/30"
        @submit.prevent="handleSend"
      >
        <textarea
          v-model="body"
          rows="1"
          :placeholder="t('chat.conversationView.placeholder')"
 class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
          @keydown.enter.exact.prevent="handleSend"
        />
        <AppButton type="submit" :label="t('chat.conversationView.send')" :loading="sending" :disabled="!body.trim()" />
      </form>
    </div>
  </AppShell>
</template>
