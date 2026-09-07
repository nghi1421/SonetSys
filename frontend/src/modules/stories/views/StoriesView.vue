<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppShell from '@/shared/components/layout/AppShell.vue'
import CreateStoryCard from '../components/CreateStoryCard.vue'
import CreateStoryModal from '../components/CreateStoryModal.vue'
import StoryPreviewCard from '../components/StoryPreviewCard.vue'
import StoryViewerModal from '../components/StoryViewerModal.vue'
import { useStoryStore } from '../store/storyStore'
import { hasUnviewed, isMine } from '../utils/storyGroups'

const storyStore = useStoryStore()
const authStore = useAuthStore()
const { t } = useI18n()

const showCreateModal = ref(false)

onMounted(() => {
  storyStore.fetchActiveStories()
})
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-3xl space-y-4">
      <h1 class="text-sm font-bold text-cyber-text">{{ t('common.stories') }}</h1>

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
        <CreateStoryCard @open="showCreateModal = true" />

        <StoryPreviewCard
          v-for="(group, index) in storyStore.storyGroups"
          :key="group.author.id ?? index"
          :group="group"
          :has-unviewed="hasUnviewed(group)"
          :is-mine="isMine(group, authStore.user?.id)"
          @open="storyStore.openViewer(index)"
        />
      </div>
    </div>

    <CreateStoryModal v-model:open="showCreateModal" />
    <StoryViewerModal />
  </AppShell>
</template>
