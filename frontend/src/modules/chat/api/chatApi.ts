import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { Conversation, Message } from '../types'

export const chatApi = {
  async fetchConversations() {
    const { data } = await http.get<ApiResponse<Conversation[]>>('/conversations')
    return data
  },

  async fetchUnreadCount() {
    const { data } = await http.get<ApiResponse<{ unread_count: number }>>('/conversations/unread-count')
    return data
  },

  async startConversation(userId: number) {
    const { data } = await http.post<ApiResponse<Conversation>>(`/users/${userId}/conversations`)
    return data
  },

  async fetchMessages(conversationId: number, cursor: string | null) {
    const { data } = await http.get<ApiResponse<Message[]>>(`/conversations/${conversationId}/messages`, {
      params: cursor ? { cursor } : {},
    })
    return data
  },

  async sendMessage(conversationId: number, body: string) {
    const { data } = await http.post<ApiResponse<Message>>(`/conversations/${conversationId}/messages`, { body })
    return data
  },
}
