<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'error' | 'warning' }>(), {
  variant: 'error',
})
</script>

<template>
  <div
    class="rounded-hud border px-4 py-3 font-mono text-xs backdrop-blur-md"
    :class="
      variant === 'error'
        ? 'border-cyber-neon-pink/30 bg-cyber-neon-pink/10 text-cyber-neon-pink'
        : 'border-amber-500/30 bg-amber-500/10 text-amber-400'
    "
  >
    <slot />
  </div>
</template>
