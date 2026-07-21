export interface ConversationParticipant {
  id: number
  name: string
  avatar_url: string | null
}

export interface Conversation {
  id: number
  other_participant: ConversationParticipant
  last_message_at: string | null
  is_unread: boolean
}

export interface Message {
  id: number
  conversation_id: number
  sender_id: number
  body: string
  created_at: string
}

export interface MessageSentPayload {
  id: number
  conversation_id: number
  sender_id: number
  body: string
  created_at: string
}
