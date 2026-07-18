import { defineStore } from 'pinia'
import { ref } from 'vue'
import { chatApi } from '../api/chatApi'
import type { Conversation, Message, MessageSentPayload } from '../types'

export const useChatStore = defineStore('chat', () => {
  const conversations = ref<Conversation[]>([])
  const conversationsLoading = ref(false)
  const unreadCount = ref(0)

  const messagesByConversation = ref<Record<number, Message[]>>({})
  const nextCursorByConversation = ref<Record<number, string | null>>({})
  const messagesLoading = ref(false)
  const messagesLoadingMore = ref(false)

  // Set by ConversationView while a thread is open, so a pushed message can
  // be appended directly instead of only bumping the unread badge.
  const activeConversationId = ref<number | null>(null)

  function setActiveConversation(conversationId: number | null): void {
    activeConversationId.value = conversationId
  }

  function markConversationRead(conversationId: number): void {
    const conversation = conversations.value.find((item) => item.id === conversationId)
    if (conversation) conversation.is_unread = false
  }

  function bumpConversationToTop(conversationId: number, lastMessageAt: string): void {
    const existing = conversations.value.find((item) => item.id === conversationId)
    if (!existing) return

    existing.last_message_at = lastMessageAt
    conversations.value = [existing, ...conversations.value.filter((item) => item.id !== conversationId)]
  }

  async function fetchConversations(): Promise<void> {
    conversationsLoading.value = true
    try {
      const response = await chatApi.fetchConversations()
      conversations.value = response.data ?? []
    } finally {
      conversationsLoading.value = false
    }
  }

  async function fetchUnreadCount(): Promise<void> {
    const response = await chatApi.fetchUnreadCount()
    unreadCount.value = response.data?.unread_count ?? 0
  }

  async function startConversation(userId: number): Promise<Conversation> {
    const response = await chatApi.startConversation(userId)
    const conversation = response.data as Conversation

    if (!conversations.value.some((item) => item.id === conversation.id)) {
      conversations.value = [conversation, ...conversations.value]
    }

    return conversation
  }

  async function fetchMessages(conversationId: number): Promise<void> {
    messagesLoading.value = true
    try {
      const response = await chatApi.fetchMessages(conversationId, null)
      messagesByConversation.value = {
        ...messagesByConversation.value,
        [conversationId]: response.data ?? [],
      }
      nextCursorByConversation.value = {
        ...nextCursorByConversation.value,
        [conversationId]: (response.meta?.next_cursor as string | null) ?? null,
      }
      // Fetching the thread marks it read server-side — reflect that locally
      // so the badge/list dot don't wait on a full conversations re-fetch.
      markConversationRead(conversationId)
    } finally {
      messagesLoading.value = false
    }
  }

  async function fetchMoreMessages(conversationId: number): Promise<void> {
    const cursor = nextCursorByConversation.value[conversationId]
    if (!cursor || messagesLoadingMore.value) return

    messagesLoadingMore.value = true
    try {
      const response = await chatApi.fetchMessages(conversationId, cursor)
      const olderMessages = response.data ?? []
      messagesByConversation.value = {
        ...messagesByConversation.value,
        [conversationId]: [...olderMessages, ...(messagesByConversation.value[conversationId] ?? [])],
      }
      nextCursorByConversation.value = {
        ...nextCursorByConversation.value,
        [conversationId]: (response.meta?.next_cursor as string | null) ?? null,
      }
    } finally {
      messagesLoadingMore.value = false
    }
  }

  async function sendMessage(conversationId: number, body: string): Promise<void> {
    const response = await chatApi.sendMessage(conversationId, body)
    const message = response.data as Message

    // Optimistically append the sender's own message from the send
    // response immediately — no push round-trip needed for this side.
    messagesByConversation.value = {
      ...messagesByConversation.value,
      [conversationId]: [...(messagesByConversation.value[conversationId] ?? []), message],
    }

    bumpConversationToTop(conversationId, message.created_at)
  }

  async function onMessagePushed(payload: MessageSentPayload): Promise<void> {
    if (activeConversationId.value === payload.conversation_id) {
      const message: Message = {
        id: payload.id,
        conversation_id: payload.conversation_id,
        sender_id: payload.sender_id,
        body: payload.body,
        created_at: payload.created_at,
      }

      messagesByConversation.value = {
        ...messagesByConversation.value,
        [payload.conversation_id]: [...(messagesByConversation.value[payload.conversation_id] ?? []), message],
      }
      bumpConversationToTop(payload.conversation_id, payload.created_at)
      return
    }

    // Thread isn't open — bump the badge and re-fetch the conversation list
    // to stay authoritative, mirroring notification's push-handling precedent.
    unreadCount.value += 1
    await fetchConversations()
  }

  return {
    conversations,
    conversationsLoading,
    unreadCount,
    messagesByConversation,
    nextCursorByConversation,
    messagesLoading,
    messagesLoadingMore,
    activeConversationId,
    setActiveConversation,
    fetchConversations,
    fetchUnreadCount,
    startConversation,
    fetchMessages,
    fetchMoreMessages,
    sendMessage,
    onMessagePushed,
  }
})
