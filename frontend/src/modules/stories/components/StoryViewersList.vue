<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { X } from '@lucide/vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import type { StoryViewer } from '../types'

defineProps<{ open: boolean; viewers: StoryViewer[] }>()
const emit = defineEmits<{ close: [] }>()

const { t } = useI18n()
</script>

<template>
  <AppModal
    :open="open"
    size="sm"
    variant="sheet"
    panel-class="max-h-[70vh]"
    @close="emit('close')"
  >
    <div class="flex items-center justify-between">
 <h2 class="text-xs font-bold text-cyber-text">
        {{ t('stories.viewersList.title') }}
      </h2>
      <button
        type="button"
        class="rounded-full p-1 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-pink"
        @click="emit('close')"
      >
        <X class="h-4 w-4" />
      </button>
    </div>

 <p v-if="viewers.length === 0" class="mt-4 text-xs text-cyber-muted">
      {{ t('stories.viewersList.empty') }}
    </p>

    <ul v-else class="mt-3 space-y-3">
      <li v-for="(entry, index) in viewers" :key="index" class="flex items-center justify-between">
 <span class="text-xs text-cyber-text">{{ entry.viewer.name }}</span>
 <span class="text-xs text-cyber-muted">
          {{ useRelativeTime(entry.viewed_at) }}
        </span>
      </li>
    </ul>
  </AppModal>
</template>
