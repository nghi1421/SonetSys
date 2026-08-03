<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Globe, Lock, LogOut, Users } from '@lucide/vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import PostCard from '@/modules/feed/components/PostCard.vue'
import PostComposer from '@/modules/feed/components/PostComposer.vue'
import { useFeedStore } from '@/modules/feed/store/feedStore'
import GroupMembersPanel from '../components/GroupMembersPanel.vue'
import GroupSettingsPanel from '../components/GroupSettingsPanel.vue'
import { useGroupStore } from '../store/groupStore'

const route = useRoute()
const groupStore = useGroupStore()
const feedStore = useFeedStore()
const { t } = useI18n()

type Tab = 'posts' | 'members' | 'settings'
const tabs: Tab[] = ['posts', 'members', 'settings']
const activeTab = ref<Tab>('posts')
const joinLeaveError = ref<string | null>(null)
const joiningOrLeaving = ref(false)

const group = computed(() => groupStore.currentGroup)
const isOwner = computed(() => group.value?.viewer_membership?.role === 'owner')
const isManager = computed(() => isOwner.value || group.value?.viewer_membership?.role === 'admin')
const isApprovedMember = computed(() => isOwner.value || group.value?.viewer_membership?.status === 'approved')
const canViewPosts = computed(() => group.value?.visibility === 'public' || isApprovedMember.value)

async function loadGroup(): Promise<void> {
  await groupStore.fetchGroup(String(route.params.slug))
  activeTab.value = 'posts'
  if (group.value && canViewPosts.value) {
    await feedStore.fetchGroupFeed(group.value.id)
  }
}

onMounted(loadGroup)
watch(() => route.params.slug, loadGroup)

async function onJoin(): Promise<void> {
  if (!group.value) return
  joiningOrLeaving.value = true
  joinLeaveError.value = null
  try {
    await groupStore.join(group.value.id)
    if (canViewPosts.value) await feedStore.fetchGroupFeed(group.value.id)
  } catch {
    joinLeaveError.value = t('groups.groupDetail.joinError')
  } finally {
    joiningOrLeaving.value = false
  }
}

async function onLeave(): Promise<void> {
  if (!group.value) return
  joiningOrLeaving.value = true
  joinLeaveError.value = null
  try {
    await groupStore.leave(group.value.id)
  } catch {
    joinLeaveError.value = t('groups.groupDetail.leaveError')
  } finally {
    joiningOrLeaving.value = false
  }
}

function createGroupPost(payload: Parameters<typeof feedStore.createGroupPost>[1]) {
  if (!group.value) return Promise.resolve()
  return feedStore.createGroupPost(group.value.id, payload)
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <div v-if="groupStore.loadingCurrent" class="h-40 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60" />

      <AppAlert v-else-if="!group">{{ t('groups.groupDetail.notFound') }}</AppAlert>

      <template v-else>
        <header class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h1 class="text-sm font-bold text-cyber-text">{{ group.name }}</h1>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-mono text-[9px] uppercase tracking-widest"
                  :class="
                    group.visibility === 'public'
                      ? 'border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 text-cyber-neon-cyan'
                      : 'border-cyber-neon-pink/30 bg-cyber-neon-pink/10 text-cyber-neon-pink'
                  "
                >
                  <Globe v-if="group.visibility === 'public'" class="h-2.5 w-2.5" />
                  <Lock v-else class="h-2.5 w-2.5" />
                  {{ t(`groups.visibility.${group.visibility}`) }}
                </span>
                <span class="inline-flex items-center gap-1.5 font-mono text-[10px] tabular-nums text-cyber-muted">
                  <Users class="h-3 w-3" />
                  {{ t('groups.groupDetail.membersCount', { count: group.members_count }) }}
                </span>
              </div>
            </div>

            <AppButton
              v-if="!group.viewer_membership"
              :label="t('groups.groupCard.join')"
              :loading="joiningOrLeaving"
              @click="onJoin"
            />
            <button
              v-else-if="group.viewer_membership.status === 'pending'"
              type="button"
              disabled
              class="shrink-0 rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-2 font-mono text-xs uppercase text-amber-400"
            >
              {{ t('groups.roles.requested') }}
            </button>
            <button
              v-else-if="!isOwner"
              type="button"
              :disabled="joiningOrLeaving"
              class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-glass px-4 py-2 font-mono text-xs uppercase text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow disabled:cursor-not-allowed disabled:opacity-40"
              @click="onLeave"
            >
              <LogOut class="h-3.5 w-3.5" /> {{ t('groups.groupDetail.leave') }}
            </button>
          </div>

          <p v-if="group.description" class="mt-3 whitespace-pre-wrap font-mono text-xs leading-relaxed text-cyber-text/90">
            {{ group.description }}
          </p>

          <AppAlert v-if="joinLeaveError" class="mt-3">{{ joinLeaveError }}</AppAlert>
        </header>

        <nav class="flex gap-1 border-b border-cyber-border">
          <button
            v-for="tab in tabs"
            v-show="tab !== 'settings' || isManager"
            :key="tab"
            type="button"
            class="rounded-t-hud px-4 py-2 font-mono text-xs uppercase tracking-widest transition-all duration-300"
            :class="
              activeTab === tab
                ? 'border-b-2 border-cyber-neon-cyan text-cyber-neon-cyan'
                : 'text-cyber-muted hover:text-cyber-text'
            "
            @click="activeTab = tab"
          >
            {{ t(`groups.groupDetail.tabs.${tab}`) }}
          </button>
        </nav>

        <div v-if="activeTab === 'posts'">
          <div v-if="!canViewPosts" class="flex flex-col items-center py-16 text-center">
            <Lock class="h-8 w-8 text-cyber-muted" />
            <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('groups.groupDetail.privateTitle') }}</p>
            <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('groups.groupDetail.privateDescription') }}</p>
          </div>

          <div v-else class="space-y-4">
            <PostComposer v-if="isApprovedMember" :on-submit="createGroupPost" />
            <AppAlert v-else variant="warning">{{ t('groups.groupDetail.joinToPost') }}</AppAlert>

            <div v-if="feedStore.loading" class="space-y-4">
              <div
                v-for="i in 3"
                :key="i"
                class="h-24 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60"
              />
            </div>

            <div v-else-if="feedStore.posts.length === 0" class="flex flex-col items-center py-16 text-center">
              <p class="text-xs font-bold text-cyber-text">{{ t('groups.groupDetail.emptyPostsTitle') }}</p>
              <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('groups.groupDetail.emptyPostsDescription') }}</p>
            </div>

            <template v-else>
              <PostCard v-for="post in feedStore.posts" :key="post.id" :post="post" />

              <div v-if="feedStore.nextCursor" class="flex justify-center pt-2">
                <AppButton
                  :label="t('groups.groupDetail.loadMore')"
                  variant="secondary"
                  :loading="feedStore.loadingMore"
                  @click="feedStore.fetchMoreGroupFeed(group.id)"
                />
              </div>
            </template>
          </div>
        </div>

        <GroupMembersPanel
          v-else-if="activeTab === 'members' && isApprovedMember"
          :group="group"
          :is-owner="isOwner"
          :is-manager="isManager"
        />
        <AppAlert v-else-if="activeTab === 'members'">{{ t('groups.groupDetail.joinToSeeMembers') }}</AppAlert>

        <GroupSettingsPanel v-else-if="activeTab === 'settings' && isManager" :group="group" :is-owner="isOwner" />
      </template>
    </div>
  </AppShell>
</template>
