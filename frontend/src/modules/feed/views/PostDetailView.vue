<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import PostCard from '../components/PostCard.vue'
import { useFeedStore } from '../store/feedStore'

const route = useRoute()
const feedStore = useFeedStore()

const post = computed(() => feedStore.posts[0] ?? null)

onMounted(load)
watch(() => route.params.id, load)

function load(): void {
  feedStore.fetchPost(Number(route.params.id))
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <div
        v-if="feedStore.loading"
        class="h-40 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60"
      />

      <AppAlert v-else-if="feedStore.notFound || !post">
        This post is unavailable — it may have been deleted, or you don't have permission to view it.
      </AppAlert>

      <PostCard v-else :post="post" />
    </div>
  </AppShell>
</template>
