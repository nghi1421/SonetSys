<script setup lang="ts">
import { Loader2 } from '@lucide/vue'

interface ButtonProps {
  label: string
  type?: 'button' | 'submit'
  loading?: boolean
  disabled?: boolean
  variant?: 'primary' | 'secondary'
}

withDefaults(defineProps<ButtonProps>(), {
  type: 'button',
  loading: false,
  disabled: false,
  variant: 'primary',
})

const emit = defineEmits<{ click: [] }>()
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center gap-2 rounded-full px-4 py-2 font-mono text-xs font-bold uppercase tracking-wider transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
    :class="
      variant === 'primary'
        ? 'bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink text-white hover:shadow-cyan-glow'
        : 'border border-cyber-border bg-cyber-glass text-cyber-text backdrop-blur-md hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow'
    "
    @click="emit('click')"
  >
    <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin" />
    {{ label }}
  </button>
</template>
