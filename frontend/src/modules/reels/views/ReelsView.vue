<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, Clapperboard, Plus } from '@lucide/vue'
import CreateReelModal from '../components/CreateReelModal.vue'
import ReelCard from '../components/ReelCard.vue'
import { useReelStore } from '../store/reelStore'

const reelStore = useReelStore()
const router = useRouter()
const { t } = useI18n()

const showCreateModal = ref(false)
const sentinelRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

onMounted(async () => {
  await reelStore.fetchReels()

  observer = new IntersectionObserver(([entry]) => {
    if (entry?.isIntersecting) reelStore.fetchMore()
  })
  if (sentinelRef.value) observer.observe(sentinelRef.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
})

function goBack(): void {
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <div class="fixed inset-0 z-40 bg-black">
    <div class="absolute inset-x-0 top-0 z-30 flex items-center justify-between p-3">
      <button
        type="button"
        class="rounded-full bg-black/40 p-2 text-white backdrop-blur-md transition-all duration-300 hover:bg-black/60"
        :aria-label="t('common.previous')"
        @click="goBack"
      >
        <ArrowLeft class="h-4 w-4" />
      </button>

      <button
        type="button"
        class="rounded-full bg-black/40 p-2 text-white backdrop-blur-md transition-all duration-300 hover:bg-black/60"
        :aria-label="t('reels.createModal.title')"
        @click="showCreateModal = true"
      >
        <Plus class="h-4 w-4" />
      </button>
    </div>

    <div
      v-if="reelStore.loading"
      class="flex h-full items-center justify-center font-mono text-xs text-cyber-muted"
    >
      {{ t('common.loading') }}
    </div>

    <div
      v-else-if="reelStore.reels.length === 0"
      class="flex h-full flex-col items-center justify-center gap-3 px-6 text-center"
    >
      <Clapperboard class="h-8 w-8 text-cyber-muted" />
      <p class="font-mono text-xs text-cyber-muted">{{ t('reels.reelsPage.empty') }}</p>
    </div>

    <div v-else class="h-full snap-y snap-mandatory overflow-y-scroll">
      <ReelCard v-for="reel in reelStore.reels" :key="reel.id" :reel="reel" />
      <div ref="sentinelRef" class="h-1" />
    </div>

    <CreateReelModal :open="showCreateModal" @update:open="showCreateModal = $event" />
  </div>
</template>
