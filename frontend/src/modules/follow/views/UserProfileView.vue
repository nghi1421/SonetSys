<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Ban, Camera, Crown, EllipsisVertical, Inbox, X } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import PostCard from '@/modules/feed/components/PostCard.vue'
import PostDetailModal from '@/modules/feed/components/PostDetailModal.vue'
import type { Post } from '@/modules/feed/types'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useChatStore } from '@/modules/chat/store/chatStore'
import FollowButton from '../components/FollowButton.vue'
import FollowListModal from '../components/FollowListModal.vue'
import { useFollowStore } from '../store/followStore'

const route = useRoute()
const router = useRouter()
const followStore = useFollowStore()
const chatStore = useChatStore()
const authStore = useAuthStore()
const { t } = useI18n()

const userId = computed(() => Number(route.params.id))
const showFollowersModal = ref(false)
const showFollowingModal = ref(false)
const startingConversation = ref(false)
const modalPost = ref<Post | null>(null)

const isOwnProfile = computed(() => authStore.user?.id === followStore.profile?.id)
const showActionsMenu = ref(false)
const blockActionLoading = ref(false)
const canShowFollowActions = computed(
  () => !followStore.profile?.is_blocked && !followStore.profile?.is_blocked_by,
)

const avatarInput = ref<HTMLInputElement | null>(null)
const coverInput = ref<HTMLInputElement | null>(null)
const avatarUploading = ref(false)
const coverUploading = ref(false)
const avatarError = ref<string | null>(null)
const coverError = ref<string | null>(null)

async function startConversation(): Promise<void> {
  startingConversation.value = true
  try {
    const conversation = await chatStore.startConversation(userId.value)
    router.push({ name: 'conversation', params: { conversationId: conversation.id } })
  } finally {
    startingConversation.value = false
  }
}

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

function syncProfileFromAuthUser(): void {
  if (!followStore.profile || !authStore.user) return
  followStore.profile.avatar_url = authStore.user.avatar_url
  followStore.profile.cover_url = authStore.user.cover_url
}

function pickAvatar(): void {
  avatarInput.value?.click()
}

function pickCover(): void {
  coverInput.value?.click()
}

async function handleAvatarChange(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  avatarError.value = null
  avatarUploading.value = true
  try {
    await authStore.updateProfile({ avatar: file })
    syncProfileFromAuthUser()
  } catch {
    avatarError.value = t('follow.profile.avatarUploadError')
  } finally {
    avatarUploading.value = false
    input.value = ''
  }
}

async function handleCoverChange(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  coverError.value = null
  coverUploading.value = true
  try {
    await authStore.updateProfile({ cover: file })
    syncProfileFromAuthUser()
  } catch {
    coverError.value = t('follow.profile.coverUploadError')
  } finally {
    coverUploading.value = false
    input.value = ''
  }
}

async function removeAvatar(): Promise<void> {
  avatarError.value = null
  avatarUploading.value = true
  try {
    await authStore.updateProfile({ removeAvatar: true })
    syncProfileFromAuthUser()
  } catch {
    avatarError.value = t('follow.profile.avatarUploadError')
  } finally {
    avatarUploading.value = false
  }
}

async function removeCover(): Promise<void> {
  coverError.value = null
  coverUploading.value = true
  try {
    await authStore.updateProfile({ removeCover: true })
    syncProfileFromAuthUser()
  } catch {
    coverError.value = t('follow.profile.coverUploadError')
  } finally {
    coverUploading.value = false
  }
}

async function toggleBlock(): Promise<void> {
  if (!followStore.profile) return
  showActionsMenu.value = false
  blockActionLoading.value = true
  try {
    if (followStore.profile.is_blocked) {
      await followStore.unblock(followStore.profile.id)
    } else {
      await followStore.block(followStore.profile.id)
    }
  } finally {
    blockActionLoading.value = false
  }
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

      <div v-else class="overflow-hidden rounded-hud border border-cyber-border bg-cyber-glass backdrop-blur-md">
        <div class="relative h-40 w-full sm:h-56">
          <img
            v-if="followStore.profile.cover_url"
            :src="followStore.profile.cover_url"
            :alt="t('follow.profile.coverAlt')"
            class="h-full w-full object-cover"
          />
          <div
            v-else
            class="h-full w-full bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink"
          />

          <div
            v-if="coverUploading"
            class="absolute inset-0 flex items-center justify-center bg-cyber-bg/60 backdrop-blur-sm"
          >
            <span
              class="h-6 w-6 animate-spin rounded-full border-2 border-cyber-neon-cyan border-t-transparent"
            />
          </div>

          <template v-if="isOwnProfile">
            <input ref="coverInput" type="file" accept="image/*" class="hidden" @change="handleCoverChange" />
            <button
              type="button"
 class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-glass px-3 py-1.5 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
              :disabled="coverUploading"
              @click="pickCover"
            >
              <Camera class="h-3 w-3" />
              {{ t('follow.profile.changeCover') }}
            </button>
            <button
              v-if="followStore.profile.cover_url"
              type="button"
              class="absolute right-3 top-11 rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
              :disabled="coverUploading"
              :aria-label="t('follow.profile.removeCover')"
              @click="removeCover"
            >
              <X class="h-3 w-3" />
            </button>
          </template>
        </div>

        <div class="p-6">
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="relative -mt-14 shrink-0">
                <img
                  v-if="followStore.profile.avatar_url"
                  :src="followStore.profile.avatar_url"
                  :alt="followStore.profile.name"
                  class="h-20 w-20 rounded-full border-4 border-cyber-bg object-cover shadow-cyan-glow"
                />
                <span
                  v-else
 class="flex h-20 w-20 items-center justify-center rounded-full border-4 border-cyber-bg bg-cyber-surface text-2xl font-bold text-cyber-text shadow-cyan-glow"
                >
                  {{ initialOf(followStore.profile.name) }}
                </span>

                <div
                  v-if="avatarUploading"
                  class="absolute inset-0 flex items-center justify-center rounded-full bg-cyber-bg/60 backdrop-blur-sm"
                >
                  <span
                    class="h-5 w-5 animate-spin rounded-full border-2 border-cyber-neon-cyan border-t-transparent"
                  />
                </div>

                <template v-if="isOwnProfile">
                  <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
                  <button
                    type="button"
                    class="absolute bottom-0 right-0 rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
                    :disabled="avatarUploading"
                    :aria-label="t('follow.profile.changeAvatar')"
                    @click="pickAvatar"
                  >
                    <Camera class="h-3 w-3" />
                  </button>
                  <button
                    v-if="followStore.profile.avatar_url"
                    type="button"
                    class="absolute -top-1 -right-1 rounded-full border border-cyber-border bg-cyber-glass p-1 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
                    :disabled="avatarUploading"
                    :aria-label="t('follow.profile.removeAvatar')"
                    @click="removeAvatar"
                  >
                    <X class="h-2.5 w-2.5" />
                  </button>
                </template>
              </div>
              <div>
                <h1 class="flex items-center gap-1.5 text-sm font-bold text-cyber-text">
                  {{ followStore.profile.name }}
                  <Crown v-if="followStore.profile.is_premium" class="h-3.5 w-3.5 shrink-0 text-cyber-neon-indigo" />
                </h1>
 <p class="mt-1 text-xs text-cyber-muted">
                  {{ t('follow.joined') }} {{ useRelativeTime(followStore.profile.created_at) }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <template v-if="canShowFollowActions">
                <AppButton
                  v-if="followStore.profile.is_following && followStore.profile.is_followed_by"
                  :label="t('follow.message')"
                  variant="secondary"
                  :loading="startingConversation"
                  @click="startConversation"
                />
                <FollowButton :user-id="followStore.profile.id" :is-following="followStore.profile.is_following" />
              </template>

              <div v-if="!isOwnProfile" class="relative">
                <button
                  type="button"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-2 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg"
                  :aria-label="t('follow.actionsLabel')"
                  @click="showActionsMenu = !showActionsMenu"
                >
                  <EllipsisVertical class="h-4 w-4" />
                </button>

                <div v-if="showActionsMenu" class="fixed inset-0 z-0" @click="showActionsMenu = false" />

                <div
                  v-if="showActionsMenu"
                  class="popover-panel absolute right-0 z-10 mt-1 w-40 rounded-hud border border-cyber-border bg-cyber-surface py-1"
                  @click.stop
                >
                  <button
                    type="button"
 class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-cyber-neon-pink transition-colors duration-300 hover:shadow-pink-glow disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="blockActionLoading"
                    @click="toggleBlock"
                  >
                    <Ban class="h-3.5 w-3.5" />
                    {{ followStore.profile.is_blocked ? t('follow.unblock') : t('follow.block') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

 <p v-if="avatarError" class="mt-3 text-xs text-cyber-neon-pink">{{ avatarError }}</p>
 <p v-if="coverError" class="mt-3 text-xs text-cyber-neon-pink">{{ coverError }}</p>

          <div class="mt-4 flex gap-6">
            <button
              type="button"
 class="text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
              @click="openFollowers"
            >
              <span class="font-bold tabular-nums">{{ followStore.profile.followers_count }}</span>
              {{ t('follow.followers') }}
            </button>
            <button
              type="button"
 class="text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
              @click="openFollowing"
            >
              <span class="font-bold tabular-nums">{{ followStore.profile.following_count }}</span>
              {{ t('follow.following') }}
            </button>
          </div>
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
        <PostCard
          v-for="post in followStore.posts"
          :key="post.id"
          :post="post"
          clickable
          @open="modalPost = post"
        />

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

      <PostDetailModal v-if="modalPost" :post="modalPost" @close="modalPost = null" />
    </div>
  </AppShell>
</template>
