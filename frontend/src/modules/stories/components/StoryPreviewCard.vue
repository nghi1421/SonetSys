<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Video } from '@lucide/vue'
import type { StoryGroup } from '../types'

const props = defineProps<{ group: StoryGroup; hasUnviewed: boolean; isMine: boolean }>()

defineEmits<{ open: [] }>()

const { t } = useI18n()

const firstStory = computed(() => props.group.stories[0] ?? null)

function initialOf(name: string | null): string {
  return (name ?? '?').trim().charAt(0).toUpperCase()
}
</script>

<template>
  <button
    v-if="firstStory"
    type="button"
    class="group relative aspect-[3/4] w-full overflow-hidden rounded-hud border border-cyber-border bg-cyber-surface text-left transition-all duration-300 hover:shadow-cyan-glow"
    @click="$emit('open')"
  >
    <img
      v-if="firstStory.media_type === 'image'"
      :src="firstStory.media_url"
      :alt="group.author.name ?? ''"
      class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
    />
    <video
      v-else
      :src="firstStory.media_url"
      muted
      playsinline
      preload="metadata"
      class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
    />

    <span
      v-if="firstStory.media_type === 'video'"
      class="absolute right-2 top-2 rounded-full bg-black/50 p-1.5 text-white"
    >
      <Video class="h-3 w-3" />
    </span>

    <span class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />

    <span
      class="absolute left-2 top-2 flex h-8 w-8 items-center justify-center rounded-full p-0.5"
      :class="
        hasUnviewed
          ? 'bg-gradient-to-br from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink shadow-cyan-glow'
          : 'bg-white/40'
      "
    >
      <span
        class="flex h-full w-full items-center justify-center rounded-full border border-white/30 bg-cyber-surface text-xs font-bold text-cyber-text"
      >
        {{ initialOf(group.author.name) }}
      </span>
    </span>

    <span
      class="absolute inset-x-2 bottom-2 w-[calc(100%-1rem)] min-w-0 truncate text-xs font-bold text-white drop-shadow"
    >
      {{ isMine ? t('stories.reel.yourStory') : group.author.name }}
    </span>
  </button>
</template>
