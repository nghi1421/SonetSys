<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { UserRound } from '@lucide/vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useUserStore } from '../store/userStore'

const userStore = useUserStore()
const { t } = useI18n()

onMounted(() => {
  userStore.fetchRecent()
})
</script>

<template>
  <section class="rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md">
    <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
      {{ t('widgets.newMembers.title') }}
    </h2>

    <div v-if="userStore.recentLoading" class="mt-3 space-y-3">
      <div v-for="i in 3" :key="i" class="flex items-center gap-2.5">
        <div class="h-8 w-8 shrink-0 animate-pulse rounded-full bg-cyber-surface/60" />
        <div class="h-2.5 w-24 animate-pulse rounded-full bg-cyber-surface/60" />
      </div>
    </div>

    <p v-else-if="userStore.recentError" class="mt-3 font-mono text-xs text-cyber-neon-pink">
      {{ t('widgets.newMembers.error') }}
    </p>

    <p v-else-if="userStore.recentUsers.length === 0" class="mt-3 font-mono text-xs text-cyber-muted">
      {{ t('widgets.newMembers.empty') }}
    </p>

    <ul v-else class="mt-3 space-y-3">
      <li v-for="user in userStore.recentUsers" :key="user.id">
        <router-link :to="`/users/${user.id}`" class="group flex items-center gap-2.5">
          <img
            v-if="user.avatar_url"
            :src="user.avatar_url"
            :alt="user.name"
            class="h-8 w-8 shrink-0 rounded-full object-cover"
          />
          <span
            v-else
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface"
          >
            <UserRound class="h-3.5 w-3.5 text-cyber-neon-cyan" />
          </span>
          <span class="min-w-0">
            <span
              class="block truncate font-mono text-xs text-cyber-text transition-colors duration-300 group-hover:text-cyber-neon-cyan"
            >
              {{ user.name }}
            </span>
            <span class="block font-mono text-xs text-cyber-muted">{{ useRelativeTime(user.created_at) }}</span>
          </span>
        </router-link>
      </li>
    </ul>
  </section>
</template>
