<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'error' | 'warning' }>(), {
  variant: 'error',
})
</script>

<template>
  <div
    class="rounded-md border px-4 py-3 text-sm"
    :class="
      variant === 'error'
        ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-300'
        : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
    "
  >
    <slot />
  </div>
</template>
