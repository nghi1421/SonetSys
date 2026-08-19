<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, ChevronRight } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useStoryStore } from '../store/storyStore'
import { hasUnviewed, isMine } from '../utils/storyGroups'
import CreateStoryCard from './CreateStoryCard.vue'
import CreateStoryModal from './CreateStoryModal.vue'
import StoryPreviewCard from './StoryPreviewCard.vue'
import StoryViewerModal from './StoryViewerModal.vue'

const SCROLL_STEP_PX = 300

const storyStore = useStoryStore()
const authStore = useAuthStore()
const { t } = useI18n()

const showCreateModal = ref(false)
const scrollerRef = ref<HTMLElement | null>(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

const groups = computed(() => storyStore.storyGroups)

function updateScrollState(): void {
  const el = scrollerRef.value
  if (!el) return
  canScrollLeft.value = el.scrollLeft > 0
  // -1px tolerance for sub-pixel rounding on some browsers/zoom levels.
  canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 1
}

function scrollByStep(direction: 1 | -1): void {
  scrollerRef.value?.scrollBy({ left: direction * SCROLL_STEP_PX, behavior: 'smooth' })
}

watch(groups, () => {
  nextTick(updateScrollState)
})

onMounted(async () => {
  await storyStore.fetchActiveStories()
  await nextTick()
  updateScrollState()
})
</script>

<template>
  <div class="relative">
    <button
      v-if="canScrollLeft"
      type="button"
      class="absolute left-1 top-1/2 z-10 -translate-y-1/2 rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
      :aria-label="t('common.previous')"
      @click="scrollByStep(-1)"
    >
      <ChevronLeft class="h-4 w-4" />
    </button>

    <div
      ref="scrollerRef"
      class="scrollbar-hide flex gap-3 overflow-x-auto pb-1"
      @scroll="updateScrollState"
    >
      <div class="w-24 shrink-0">
        <CreateStoryCard @open="showCreateModal = true" />
      </div>

      <div v-for="(group, index) in groups" :key="group.author.id ?? index" class="w-24 shrink-0">
        <StoryPreviewCard
          :group="group"
          :has-unviewed="hasUnviewed(group)"
          :is-mine="isMine(group, authStore.user?.id)"
          @open="storyStore.openViewer(index)"
        />
      </div>
    </div>

    <button
      v-if="canScrollRight"
      type="button"
      class="absolute right-1 top-1/2 z-10 -translate-y-1/2 rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
      :aria-label="t('common.next')"
      @click="scrollByStep(1)"
    >
      <ChevronRight class="h-4 w-4" />
    </button>

    <CreateStoryModal v-model:open="showCreateModal" />
    <StoryViewerModal />
  </div>
</template>
