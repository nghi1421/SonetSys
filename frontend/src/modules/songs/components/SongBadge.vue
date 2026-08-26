<script setup lang="ts">
import { Music } from '@lucide/vue'
import type { Song } from '../types'

defineProps<{ song: Song }>()
</script>

<template>
  <div
    class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-cyber-border bg-black/40 px-2.5 py-1 text-xs text-white backdrop-blur-md"
  >
    <Music class="h-3 w-3 shrink-0 text-cyber-neon-cyan" />
    <span class="truncate">
      {{ song.title }}<template v-if="song.artist"> · {{ song.artist }}</template>
    </span>
  </div>
</template>
