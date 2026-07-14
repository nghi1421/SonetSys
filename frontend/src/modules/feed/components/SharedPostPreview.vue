<script setup lang="ts">
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import PostMedia from './PostMedia.vue'
import type { Post } from '../types'

defineProps<{ post: Post }>()
</script>

<template>
  <div class="rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
    <p class="text-sm font-medium text-slate-900 dark:text-zinc-100">{{ post.author.name }}</p>
    <p class="text-xs text-slate-500 dark:text-zinc-400">{{ useRelativeTime(post.created_at) }}</p>
    <p v-if="post.body" class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-zinc-300">{{ post.body }}</p>
    <PostMedia v-if="post.media_type" :post="post" class="mt-2" />
  </div>
</template>
