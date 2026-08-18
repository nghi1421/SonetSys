<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import PostCard from '../components/PostCard.vue'
import { useFeedStore } from '../store/feedStore'

const route = useRoute()
const feedStore = useFeedStore()
const { t } = useI18n()

const post = computed(() => feedStore.posts[0] ?? null)

onMounted(load)
watch(() => route.params.id, load)

function load(): void {
  feedStore.fetchPost(Number(route.params.id))
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-3xl space-y-4">
      <div
        v-if="feedStore.loading"
        class="h-40 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60"
      />

      <AppAlert v-else-if="feedStore.notFound || !post">
        {{ t('feed.postDetail.unavailable') }}
      </AppAlert>

      <PostCard v-else :post="post" />
    </div>
  </AppShell>
</template>
