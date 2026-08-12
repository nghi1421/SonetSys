<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { Hash, Inbox } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import PostCard from '../components/PostCard.vue'
import PostDetailModal from '../components/PostDetailModal.vue'
import { useFeedStore } from '../store/feedStore'
import type { Post } from '../types'

const route = useRoute()
const feedStore = useFeedStore()
const { t } = useI18n()

const modalPost = ref<Post | null>(null)
const tag = computed(() => String(route.params.tag ?? '').toLowerCase())

function load(): void {
  feedStore.fetchHashtagFeed(tag.value)
}

onMounted(load)
watch(tag, load)
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <header class="flex items-center gap-2 rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md">
        <Hash class="h-5 w-5 text-cyber-neon-cyan" />
 <h1 class="text-sm font-bold text-cyber-text">#{{ tag }}</h1>
      </header>

      <div v-if="feedStore.hashtagLoading" class="space-y-4">
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

      <div v-else-if="feedStore.hashtagPosts.length === 0" class="flex flex-col items-center py-16 text-center">
        <Inbox class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('feed.hashtagView.emptyTitle') }}</p>
 <p class="mt-1 text-xs text-cyber-muted">
          {{ t('feed.hashtagView.emptyDescription', { tag }) }}
        </p>
      </div>

      <template v-else>
        <PostCard
          v-for="post in feedStore.hashtagPosts"
          :key="post.id"
          :post="post"
          clickable
          @open="modalPost = post"
        />

        <div v-if="feedStore.hashtagNextCursor" class="flex justify-center pt-2">
          <AppButton
            :label="t('feed.feedView.loadMore')"
            variant="secondary"
            :loading="feedStore.hashtagLoadingMore"
            @click="feedStore.fetchMoreHashtagFeed(tag)"
          />
        </div>
      </template>
    </div>

    <PostDetailModal v-if="modalPost" :post="modalPost" @close="modalPost = null" />
  </AppShell>
</template>
