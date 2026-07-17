<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { X } from '@lucide/vue'
import FollowButton from './FollowButton.vue'
import type { FollowUser } from '../types'

defineProps<{ open: boolean; mode: 'followers' | 'following'; users: FollowUser[] }>()
const emit = defineEmits<{ close: [] }>()

const { t } = useI18n()

function initialOf(name: string): string {
  return name.trim().charAt(0).toUpperCase()
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center" @click="emit('close')">
      <div class="absolute inset-0 bg-cyber-bg/80 backdrop-blur-sm" />

      <div
        class="relative max-h-[70vh] w-full max-w-sm overflow-y-auto rounded-t-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md sm:rounded-hud"
        @click.stop
      >
        <div class="flex items-center justify-between">
          <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
            {{ mode === 'followers' ? t('follow.followersTitle') : t('follow.followingTitle') }}
          </h2>
          <button
            type="button"
            class="rounded-full p-1 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-pink"
            @click="emit('close')"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <p v-if="users.length === 0" class="mt-4 font-mono text-xs text-cyber-muted">
          {{ mode === 'followers' ? t('follow.noFollowers') : t('follow.noFollowing') }}
        </p>

        <ul v-else class="mt-3 space-y-3">
          <li v-for="user in users" :key="user.id" class="flex items-center justify-between gap-3">
            <router-link :to="`/users/${user.id}`" class="flex min-w-0 items-center gap-2" @click="emit('close')">
              <img
                v-if="user.avatar_url"
                :src="user.avatar_url"
                :alt="user.name"
                class="h-8 w-8 shrink-0 rounded-full object-cover"
              />
              <span
                v-else
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface font-mono text-xs font-bold text-cyber-text"
              >
                {{ initialOf(user.name) }}
              </span>
              <span class="truncate font-mono text-xs text-cyber-text">{{ user.name }}</span>
            </router-link>
            <FollowButton :user-id="user.id" :is-following="user.is_following" />
          </li>
        </ul>
      </div>
    </div>
  </Teleport>
</template>
