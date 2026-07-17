<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Plus } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useStoryStore } from '../store/storyStore'
import CreateStoryModal from './CreateStoryModal.vue'
import StoryViewerModal from './StoryViewerModal.vue'

const storyStore = useStoryStore()
const authStore = useAuthStore()
const { t } = useI18n()

const showCreateModal = ref(false)

const groups = computed(() => storyStore.storyGroups)

function initialOf(name: string | null): string {
  return (name ?? '?').trim().charAt(0).toUpperCase()
}

function hasUnviewed(groupIndex: number): boolean {
  return groups.value[groupIndex]?.stories.some((story) => !story.viewed_by_me) ?? false
}

function isMine(groupIndex: number): boolean {
  return groups.value[groupIndex]?.author.id === authStore.user?.id
}

onMounted(() => {
  storyStore.fetchActiveStories()
})
</script>

<template>
  <div class="flex gap-3 overflow-x-auto pb-1">
    <button
      type="button"
      class="flex w-16 shrink-0 flex-col items-center gap-1.5"
      :aria-label="t('stories.reel.addStory')"
      @click="showCreateModal = true"
    >
      <span
        class="flex h-14 w-14 items-center justify-center rounded-full border border-dashed border-cyber-border bg-cyber-glass text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
      >
        <Plus class="h-5 w-5" />
      </span>
      <span class="truncate font-mono text-[9px] uppercase tracking-widest text-cyber-muted">
        {{ t('stories.reel.addStory') }}
      </span>
    </button>

    <button
      v-for="(group, index) in groups"
      :key="group.author.id ?? index"
      type="button"
      class="flex w-16 shrink-0 flex-col items-center gap-1.5"
      @click="storyStore.openViewer(index)"
    >
      <span
        class="flex h-14 w-14 items-center justify-center rounded-full p-0.5 transition-all duration-300"
        :class="
          hasUnviewed(index)
            ? 'bg-gradient-to-br from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink shadow-cyan-glow'
            : 'bg-cyber-border'
        "
      >
        <span
          class="flex h-full w-full items-center justify-center rounded-full border border-cyber-border bg-cyber-surface font-mono text-sm font-bold text-cyber-text"
        >
          {{ initialOf(group.author.name) }}
        </span>
      </span>
      <span class="truncate font-mono text-[9px] uppercase tracking-widest text-cyber-muted">
        {{ isMine(index) ? t('stories.reel.yourStory') : group.author.name }}
      </span>
    </button>

    <CreateStoryModal v-model:open="showCreateModal" />
    <StoryViewerModal />
  </div>
</template>
