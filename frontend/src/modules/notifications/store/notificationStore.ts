import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notificationApi } from '../api/notificationApi'
import type { AppNotification } from '../types'

export const useNotificationStore = defineStore('notifications', () => {
  const items = ref<AppNotification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const justReceived = ref(false)

  async function fetchNotifications(): Promise<void> {
    loading.value = true
    try {
      const response = await notificationApi.list()
      items.value = response.data ?? []
      unreadCount.value = (response.meta?.unread_count as number) ?? 0
    } finally {
      loading.value = false
    }
  }

  async function receivePushed(): Promise<void> {
    // The push payload only carries actor_id (no name) — re-fetch so the
    // list/badge stay authoritative rather than hand-building a partial item.
    await fetchNotifications()
    justReceived.value = true
    window.setTimeout(() => {
      justReceived.value = false
    }, 3000)
  }

  async function markAsRead(notificationId: string): Promise<void> {
    const item = items.value.find((notification) => notification.id === notificationId)
    if (!item || item.read_at) return

    await notificationApi.markAsRead(notificationId)
    item.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }

  async function markAllAsRead(): Promise<void> {
    await notificationApi.markAllAsRead()
    const readAt = new Date().toISOString()
    items.value.forEach((notification) => {
      if (!notification.read_at) notification.read_at = readAt
    })
    unreadCount.value = 0
  }

  return {
    items,
    unreadCount,
    loading,
    justReceived,
    fetchNotifications,
    receivePushed,
    markAsRead,
    markAllAsRead,
  }
})
