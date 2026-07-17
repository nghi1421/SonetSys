<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Inbox } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import PostCard from '@/modules/feed/components/PostCard.vue'
import FollowButton from '../components/FollowButton.vue'
import FollowListModal from '../components/FollowListModal.vue'
import { useFollowStore } from '../store/followStore'

const route = useRoute()
const followStore = useFollowStore()
const { t } = useI18n()

const userId = computed(() => Number(route.params.id))
const showFollowersModal = ref(false)
const showFollowingModal = ref(false)

function initialOf(name: string): string {
  return name.trim().charAt(0).toUpperCase()
}

function load(): void {
  followStore.fetchProfile(userId.value)
  followStore.fetchProfilePosts(userId.value)
}

async function openFollowers(): Promise<void> {
  await followStore.fetchFollowers(userId.value)
  showFollowersModal.value = true
}

async function openFollowing(): Promise<void> {
  await followStore.fetchFollowing(userId.value)
  showFollowingModal.value = true
}

onMounted(load)
watch(userId, load)
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <div
        v-if="followStore.profileLoading"
        class="h-32 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 backdrop-blur-md"
      />

      <AppAlert v-else-if="followStore.notFound || !followStore.profile">
        {{ t('follow.profileUnavailable') }}
      </AppAlert>

      <div v-else class="rounded-hud border border-cyber-border bg-cyber-glass p-6 backdrop-blur-md">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-4">
            <img
              v-if="followStore.profile.avatar_url"
              :src="followStore.profile.avatar_url"
              :alt="followStore.profile.name"
              class="h-16 w-16 rounded-full object-cover"
            />
            <span
              v-else
              class="flex h-16 w-16 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface font-mono text-2xl font-bold text-cyber-text"
            >
              {{ initialOf(followStore.profile.name) }}
            </span>
            <div>
              <h1 class="text-sm font-bold tracking-wider text-cyber-text">// {{ followStore.profile.name }}</h1>
              <p class="mt-1 font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
                {{ t('follow.joined') }} {{ useRelativeTime(followStore.profile.created_at) }}
              </p>
            </div>
          </div>
          <FollowButton :user-id="followStore.profile.id" :is-following="followStore.profile.is_following" />
        </div>

        <div class="mt-4 flex gap-6">
          <button
            type="button"
            class="font-mono text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
            @click="openFollowers"
          >
            <span class="font-bold tabular-nums">{{ followStore.profile.followers_count }}</span>
            {{ t('follow.followers') }}
          </button>
          <button
            type="button"
            class="font-mono text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
            @click="openFollowing"
          >
            <span class="font-bold tabular-nums">{{ followStore.profile.following_count }}</span>
            {{ t('follow.following') }}
          </button>
        </div>
      </div>

      <div v-if="followStore.postsLoading" class="space-y-4">
        <div
          v-for="i in 2"
          :key="i"
          class="animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 p-4 backdrop-blur-md"
        >
          <div class="h-3 w-32 rounded-full bg-cyber-border" />
          <div class="mt-3 h-2.5 w-full rounded-full bg-cyber-border" />
        </div>
      </div>

      <div v-else-if="followStore.posts.length === 0" class="flex flex-col items-center py-16 text-center">
        <Inbox class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('follow.noPosts') }}</p>
      </div>

      <template v-else>
        <PostCard v-for="post in followStore.posts" :key="post.id" :post="post" />

        <div v-if="followStore.nextCursor" class="flex justify-center pt-2">
          <AppButton
            :label="t('feed.feedView.loadMore')"
            variant="secondary"
            :loading="followStore.postsLoadingMore"
            @click="followStore.fetchMoreProfilePosts(userId)"
          />
        </div>
      </template>

      <FollowListModal
        :open="showFollowersModal"
        mode="followers"
        :users="followStore.followers"
        @close="showFollowersModal = false"
      />
      <FollowListModal
        :open="showFollowingModal"
        mode="following"
        :users="followStore.following"
        @close="showFollowingModal = false"
      />
    </div>
  </AppShell>
</template>
