<script setup lang="ts">
import { onMounted } from 'vue'
import { Inbox } from '@lucide/vue'
import AppHeader from '@/shared/components/layout/AppHeader.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import PostCard from '../components/PostCard.vue'
import PostComposer from '../components/PostComposer.vue'
import { useFeedStore } from '../store/feedStore'

const feedStore = useFeedStore()

onMounted(() => {
  feedStore.fetchFeed()
})
</script>

<template>
  <div class="min-h-screen">
    <AppHeader />

    <main class="mx-auto max-w-2xl space-y-4 px-4 py-6">
      <PostComposer />

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
        <p class="mt-4 text-xs font-bold text-cyber-text">No posts yet</p>
        <p class="mt-1 font-mono text-xs text-cyber-muted">
          Be the first to share something with your community.
        </p>
      </div>

      <template v-else>
        <PostCard v-for="post in feedStore.posts" :key="post.id" :post="post" />

        <div v-if="feedStore.nextCursor" class="flex justify-center pt-2">
          <AppButton
            label="Load more"
            variant="secondary"
            :loading="feedStore.loadingMore"
            @click="feedStore.fetchMore"
          />
        </div>
      </template>
    </main>
  </div>
</template>
