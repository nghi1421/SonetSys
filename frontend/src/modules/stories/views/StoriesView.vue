<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Clapperboard } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import StoriesReel from '../components/StoriesReel.vue'
import { useStoryStore } from '../store/storyStore'

const storyStore = useStoryStore()
const { t } = useI18n()
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-3xl space-y-4">
      <h1 class="text-sm font-bold text-cyber-text">{{ t('common.stories') }}</h1>

      <StoriesReel />

      <div
        v-if="!storyStore.loading && storyStore.storyGroups.length === 0"
        class="flex flex-col items-center py-16 text-center"
      >
        <Clapperboard class="h-8 w-8 text-cyber-muted" />
 <p class="mt-4 text-xs text-cyber-muted">{{ t('stories.storiesPage.empty') }}</p>
      </div>
    </div>
  </AppShell>
</template>
