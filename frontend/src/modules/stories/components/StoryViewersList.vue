<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { X } from '@lucide/vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import type { StoryViewer } from '../types'

defineProps<{ open: boolean; viewers: StoryViewer[] }>()
const emit = defineEmits<{ close: [] }>()

const { t } = useI18n()
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center" @click="emit('close')">
      <div class="absolute inset-0 bg-cyber-bg/80 backdrop-blur-sm" />

      <div
        class="relative max-h-[70vh] w-full max-w-sm overflow-y-auto rounded-t-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md sm:rounded-hud"
        @click.stop
      >
        <div class="flex items-center justify-between">
          <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
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

        <p v-if="viewers.length === 0" class="mt-4 font-mono text-xs text-cyber-muted">
          {{ t('stories.viewersList.empty') }}
        </p>

        <ul v-else class="mt-3 space-y-3">
          <li v-for="(entry, index) in viewers" :key="index" class="flex items-center justify-between">
            <span class="font-mono text-xs text-cyber-text">{{ entry.viewer.name }}</span>
            <span class="font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
              {{ useRelativeTime(entry.viewed_at) }}
            </span>
          </li>
        </ul>
      </div>
    </div>
  </Teleport>
</template>
