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
  <div class="min-h-screen bg-zinc-50 dark:bg-black">
    <AppHeader />

    <main class="mx-auto max-w-2xl space-y-4 px-4 py-6">
      <PostComposer />

      <div v-if="feedStore.loading" class="space-y-4">
        <div
          v-for="i in 3"
          :key="i"
          class="animate-pulse rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
        >
          <div class="h-4 w-32 rounded bg-zinc-200 dark:bg-zinc-700" />
          <div class="mt-3 h-3 w-full rounded bg-zinc-200 dark:bg-zinc-700" />
          <div class="mt-2 h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700" />
        </div>
      </div>

      <div v-else-if="feedStore.posts.length === 0" class="flex flex-col items-center py-16 text-center">
        <Inbox class="h-10 w-10 text-slate-300 dark:text-zinc-600" />
        <p class="mt-4 text-sm font-medium text-slate-900 dark:text-zinc-100">No posts yet</p>
        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
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
