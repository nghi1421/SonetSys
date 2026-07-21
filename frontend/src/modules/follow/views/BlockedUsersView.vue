<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ShieldOff } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useFollowStore } from '../store/followStore'

const followStore = useFollowStore()
const { t } = useI18n()

function initialOf(name: string): string {
  return name.trim().charAt(0).toUpperCase()
}

onMounted(() => {
  followStore.fetchBlockedUsers()
})
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// {{ t('follow.blockedUsers.title') }}</h1>

      <div v-if="followStore.blockedUsersLoading" class="space-y-3">
        <div
          v-for="i in 3"
          :key="i"
          class="h-14 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 backdrop-blur-md"
        />
      </div>

      <div
        v-else-if="followStore.blockedUsers.length === 0"
        class="flex flex-col items-center rounded-hud border border-cyber-border bg-cyber-glass py-16 text-center backdrop-blur-md"
      >
        <ShieldOff class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('follow.blockedUsers.empty') }}</p>
      </div>

      <ul v-else class="space-y-3">
        <li
          v-for="user in followStore.blockedUsers"
          :key="user.id"
          class="flex items-center justify-between gap-3 rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md"
        >
          <router-link :to="`/users/${user.id}`" class="flex min-w-0 items-center gap-3">
            <img
              v-if="user.avatar_url"
              :src="user.avatar_url"
              :alt="user.name"
              class="h-10 w-10 shrink-0 rounded-full object-cover"
            />
            <span
              v-else
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface font-mono text-sm font-bold text-cyber-text"
            >
              {{ initialOf(user.name) }}
            </span>
            <span class="truncate font-mono text-xs text-cyber-text">{{ user.name }}</span>
          </router-link>
          <AppButton
            :label="t('follow.unblock')"
            variant="secondary"
            @click="followStore.unblock(user.id)"
          />
        </li>
      </ul>
    </div>
  </AppShell>
</template>
