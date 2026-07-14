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
    class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
    :class="
      variant === 'primary'
        ? 'bg-accent-600 text-white hover:bg-accent-700 focus:ring-accent-500'
        : 'bg-white text-slate-900 border border-zinc-200 hover:bg-zinc-50 focus:ring-accent-500 dark:bg-zinc-800 dark:text-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700'
    "
    @click="emit('click')"
  >
    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
    {{ label }}
  </button>
</template>
