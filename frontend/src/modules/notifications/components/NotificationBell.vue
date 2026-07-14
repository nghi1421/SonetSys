<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Bell } from '@lucide/vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useNotificationStore } from '../store/notificationStore'
import { describeNotification } from '../utils/describeNotification'

const notificationStore = useNotificationStore()
const open = ref(false)

onMounted(() => {
  notificationStore.fetchNotifications()
})

function toggle(): void {
  open.value = !open.value
}

async function onItemClick(notificationId: string): Promise<void> {
  await notificationStore.markAsRead(notificationId)
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="relative rounded-md p-2 text-slate-500 transition-colors duration-200 hover:bg-zinc-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-accent-500 dark:text-zinc-400 dark:hover:bg-zinc-800"
      aria-label="Notifications"
      @click="toggle"
    >
      <Bell class="h-5 w-5" />
      <span
        v-if="notificationStore.unreadCount > 0"
        class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-medium text-white"
      >
        {{ notificationStore.unreadCount > 9 ? '9+' : notificationStore.unreadCount }}
      </span>
    </button>

    <div v-if="open" class="fixed inset-0 z-0" @click="open = false" />

    <div
      v-if="open"
      class="absolute right-0 z-10 mt-2 w-80 rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
      @click.stop
    >
      <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2 dark:border-zinc-800">
        <p class="text-sm font-medium text-slate-900 dark:text-zinc-100">Notifications</p>
        <button
          v-if="notificationStore.unreadCount > 0"
          type="button"
          class="text-xs text-accent-600 hover:text-accent-700"
          @click="notificationStore.markAllAsRead"
        >
          Mark all read
        </button>
      </div>

      <div
        v-if="notificationStore.items.length === 0"
        class="px-4 py-8 text-center text-sm text-slate-500 dark:text-zinc-400"
      >
        No notifications yet
      </div>

      <ul v-else class="max-h-80 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800">
        <li
          v-for="item in notificationStore.items"
          :key="item.id"
          class="cursor-pointer px-4 py-3 text-sm transition-colors duration-200 hover:bg-zinc-50 dark:hover:bg-zinc-800"
          :class="!item.read_at && 'bg-accent-50 dark:bg-accent-950/30'"
          @click="onItemClick(item.id)"
        >
          <p class="text-slate-700 dark:text-zinc-300">{{ describeNotification(item) }}</p>
          <p class="mt-0.5 text-xs text-slate-400 dark:text-zinc-500">
            {{ item.created_at ? useRelativeTime(item.created_at) : '' }}
          </p>
        </li>
      </ul>
    </div>
  </div>
</template>
