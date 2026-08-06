<script setup lang="ts">
import AppUsername from '@/shared/components/ui/AppUsername.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import PostMedia from './PostMedia.vue'
import type { Post } from '../types'

defineProps<{ post: Post }>()
</script>

<template>
  <div class="rounded-hud border border-cyber-border bg-cyber-surface/40 p-3 backdrop-blur-md">
    <AppUsername :user="post.author" />
    <p class="mt-0.5 font-mono text-xs uppercase tracking-widest text-cyber-muted">
      {{ useRelativeTime(post.created_at) }}
    </p>
    <p
      v-if="post.body"
      class="mt-2 whitespace-pre-wrap border-l border-cyber-neon-indigo pl-2 font-mono text-xs leading-relaxed text-cyber-text/90"
    >
      {{ post.body }}
    </p>
    <PostMedia v-if="post.media_type" :post="post" class="mt-2" />
  </div>
</template>
