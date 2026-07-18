import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import axios from 'axios'
import { getStoredToken } from './api/tokenStorage'
import { useNotificationStore } from '@/modules/notifications/store/notificationStore'
import { useChatStore } from '@/modules/chat/store/chatStore'
import type { MessageSentPayload } from '@/modules/chat/types'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

interface ChannelAuthData {
  auth: string
  channel_data?: string
  shared_secret?: string
}

let echo: Echo<'pusher'> | null = null

function broadcastingAuthEndpoint(): string {
  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1'
  return apiBaseUrl.replace(/\/api\/v\d+\/?$/, '') + '/broadcasting/auth'
}

export function connectEcho(userId: number): void {
  if (echo) return

  const key = import.meta.env.VITE_PUSHER_KEY
  if (!key) {
    // No Pusher app configured (e.g. local dev without credentials) — degrade
    // to the existing fetch-on-mount notification behavior rather than
    // attempting a connection that would fail anyway.
    return
  }

  try {
    window.Pusher = Pusher

    echo = new Echo({
      broadcaster: 'pusher',
      key,
      cluster: import.meta.env.VITE_PUSHER_CLUSTER,
      forceTLS: true,
      authorizer: (channel: { name: string }) => ({
        authorize(socketId: string, callback: (error: Error | null, data: ChannelAuthData | null) => void) {
          axios
            .post(
              broadcastingAuthEndpoint(),
              { socket_id: socketId, channel_name: channel.name },
              {
                headers: {
                  Authorization: `Bearer ${getStoredToken()}`,
                  Accept: 'application/json',
                },
              },
            )
            .then((response) => callback(null, response.data))
            .catch((error) => callback(error, null))
        },
      }),
    })

    const userChannel = echo.private(`user.${userId}`)

    userChannel.listen('.notification.created', () => {
      useNotificationStore().receivePushed()
    })

    userChannel.listen('.message.sent', (payload: MessageSentPayload) => {
      useChatStore().onMessagePushed(payload)
    })
  } catch (error) {
    // Real-time push is an enhancement, never a login/session blocker —
    // the app already works via fetch-on-mount without it.
    console.warn('Failed to connect real-time notifications:', error)
    echo = null
  }
}

export function disconnectEcho(): void {
  echo?.disconnect()
  echo = null
}
