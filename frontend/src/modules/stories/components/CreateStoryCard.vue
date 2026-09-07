<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Plus } from '@lucide/vue'

defineEmits<{ open: [] }>()

const { t } = useI18n()
</script>

<template>
  <button
    type="button"
    class="flex aspect-[3/4] w-full flex-col items-center justify-center gap-2 rounded-hud border border-dashed border-cyber-border bg-cyber-glass text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
    @click="$emit('open')"
  >
    <span class="flex h-10 w-10 items-center justify-center rounded-full border border-current">
      <Plus class="h-5 w-5" />
    </span>
    <span class="text-xs font-bold">{{ t('stories.reel.addStory') }}</span>
  </button>
</template>
