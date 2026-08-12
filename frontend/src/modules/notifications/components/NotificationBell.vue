<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Bell } from '@lucide/vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useNotificationStore } from '../store/notificationStore'
import { describeNotification } from '../utils/describeNotification'
import { resolveNotificationRoute } from '../utils/resolveNotificationRoute'
import type { AppNotification } from '../types'

const notificationStore = useNotificationStore()
const router = useRouter()
const { t } = useI18n()
const open = ref(false)

onMounted(() => {
  notificationStore.fetchNotifications()
})

function toggle(): void {
  open.value = !open.value
}

async function onItemClick(notification: AppNotification): Promise<void> {
  await notificationStore.markAsRead(notification.id)
  open.value = false

  const target = resolveNotificationRoute(notification)
  if (target) router.push(target)
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="relative rounded-full border border-cyber-border bg-cyber-glass p-2 text-cyber-muted backdrop-blur-none transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg"
      :class="notificationStore.justReceived && 'border-cyber-neon-cyan/60 text-cyber-neon-cyan shadow-cyan-glow'"
      :aria-label="t('notifications.bell.ariaLabel')"
      @click="toggle"
    >
      <Bell class="h-4 w-4" :class="notificationStore.justReceived && 'animate-pulse'" />
      <span
        v-if="notificationStore.unreadCount > 0"
 class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-cyber-neon-pink px-1 text-xs font-bold text-white shadow-pink-glow"
      >
        {{ notificationStore.unreadCount > 9 ? '9+' : notificationStore.unreadCount }}
      </span>
    </button>

    <div v-if="open" class="fixed inset-0 z-0" @click="open = false" />

    <div
      v-if="open"
      class="fixed inset-x-4 top-16 z-10 rounded-hud border border-cyber-neon-cyan/40 bg-cyber-surface shadow-cyan-glow sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-80"
      @click.stop
    >
      <div class="flex items-center justify-between border-b border-cyber-border px-4 py-2.5">
 <p class="text-xs text-cyber-neon-cyan">{{ t('notifications.bell.title') }}</p>
        <button
          v-if="notificationStore.unreadCount > 0"
          type="button"
 class="text-xs text-cyber-neon-indigo transition-colors duration-300 hover:text-cyber-neon-cyan"
          @click="notificationStore.markAllAsRead"
        >
          {{ t('notifications.bell.markAllRead') }}
        </button>
      </div>

      <div
        v-if="notificationStore.items.length === 0"
 class="px-4 py-8 text-center text-xs text-cyber-muted"
      >
        {{ t('notifications.bell.empty') }}
      </div>

      <ul v-else class="max-h-80 divide-y divide-cyber-border overflow-y-auto">
        <li
          v-for="item in notificationStore.items"
          :key="item.id"
          class="cursor-pointer px-4 py-3 transition-all duration-300 hover:bg-cyber-surface/60"
          :class="!item.read_at && 'bg-cyber-neon-indigo/10'"
          @click="onItemClick(item)"
        >
 <p class="text-xs text-cyber-text/90">{{ describeNotification(item, t) }}</p>
 <p class="mt-0.5 text-xs text-cyber-muted">
            {{ item.created_at ? useRelativeTime(item.created_at) : '' }}
          </p>
        </li>
      </ul>
    </div>
  </div>
</template>
