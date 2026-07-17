<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Inbox } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import StoriesReel from '@/modules/stories/components/StoriesReel.vue'
import PostCard from '../components/PostCard.vue'
import PostComposer from '../components/PostComposer.vue'
import { useFeedStore } from '../store/feedStore'

type Tab = 'forYou' | 'following'
const tabs: Tab[] = ['forYou', 'following']
const activeTab = ref<Tab>('forYou')

const feedStore = useFeedStore()
const { t } = useI18n()

function selectTab(tab: Tab): void {
  activeTab.value = tab
  if (tab === 'following' && feedStore.followingPosts.length === 0) {
    feedStore.fetchFollowingFeed()
  }
}

onMounted(() => {
  feedStore.fetchFeed()
})
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <StoriesReel />

      <PostComposer :on-submit="feedStore.createPost" />

      <nav class="flex gap-1 border-b border-cyber-border">
        <button
          v-for="tab in tabs"
          :key="tab"
          type="button"
          class="rounded-t-hud px-4 py-2 font-mono text-xs uppercase tracking-widest transition-all duration-300"
          :class="
            activeTab === tab
              ? 'border-b-2 border-cyber-neon-cyan text-cyber-neon-cyan'
              : 'text-cyber-muted hover:text-cyber-text'
          "
          @click="selectTab(tab)"
        >
          {{ t(`feed.feedView.tabs.${tab}`) }}
        </button>
      </nav>

      <template v-if="activeTab === 'forYou'">
        <div v-if="feedStore.loading" class="space-y-4">
          <div
            v-for="i in 3"
            :key="i"
            class="animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 p-4 backdrop-blur-md"
          >
            <div class="h-3 w-32 rounded-full bg-cyber-border" />
            <div class="mt-3 h-2.5 w-full rounded-full bg-cyber-border" />
            <div class="mt-2 h-2.5 w-2/3 rounded-full bg-cyber-border" />
          </div>
        </div>

        <div v-else-if="feedStore.posts.length === 0" class="flex flex-col items-center py-16 text-center">
          <Inbox class="h-8 w-8 text-cyber-muted" />
          <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('feed.feedView.emptyTitle') }}</p>
          <p class="mt-1 font-mono text-xs text-cyber-muted">
            {{ t('feed.feedView.emptyDescription') }}
          </p>
        </div>

        <template v-else>
          <PostCard v-for="post in feedStore.posts" :key="post.id" :post="post" />

          <div v-if="feedStore.nextCursor" class="flex justify-center pt-2">
            <AppButton
              :label="t('feed.feedView.loadMore')"
              variant="secondary"
              :loading="feedStore.loadingMore"
              @click="feedStore.fetchMore"
            />
          </div>
        </template>
      </template>

      <template v-else>
        <div v-if="feedStore.followingLoading" class="space-y-4">
          <div
            v-for="i in 3"
            :key="i"
            class="animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 p-4 backdrop-blur-md"
          >
            <div class="h-3 w-32 rounded-full bg-cyber-border" />
            <div class="mt-3 h-2.5 w-full rounded-full bg-cyber-border" />
            <div class="mt-2 h-2.5 w-2/3 rounded-full bg-cyber-border" />
          </div>
        </div>

        <div v-else-if="feedStore.followingPosts.length === 0" class="flex flex-col items-center py-16 text-center">
          <Inbox class="h-8 w-8 text-cyber-muted" />
          <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('feed.feedView.followingEmptyTitle') }}</p>
          <p class="mt-1 font-mono text-xs text-cyber-muted">
            {{ t('feed.feedView.followingEmptyDescription') }}
          </p>
        </div>

        <template v-else>
          <PostCard v-for="post in feedStore.followingPosts" :key="post.id" :post="post" />

          <div v-if="feedStore.followingNextCursor" class="flex justify-center pt-2">
            <AppButton
              :label="t('feed.feedView.loadMore')"
              variant="secondary"
              :loading="feedStore.followingLoadingMore"
              @click="feedStore.fetchMoreFollowingFeed"
            />
          </div>
        </template>
      </template>
    </div>
  </AppShell>
</template>
