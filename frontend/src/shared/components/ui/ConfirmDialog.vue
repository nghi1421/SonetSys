<script setup lang="ts">
interface ConfirmDialogProps {
  open: boolean
  title: string
  message: string
}

defineProps<ConfirmDialogProps>()

const emit = defineEmits<{ confirm: []; cancel: [] }>()
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click="emit('cancel')">
    <div
      class="w-full max-w-sm rounded-lg bg-white p-5 shadow-lg dark:bg-zinc-900"
      @click.stop
    >
      <h2 class="text-base font-semibold text-slate-900 dark:text-zinc-100">{{ title }}</h2>
      <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">{{ message }}</p>
      <div class="mt-4 flex justify-end gap-2">
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-sm text-slate-600 transition-colors duration-200 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
          @click="emit('cancel')"
        >
          Cancel
        </button>
        <button
          type="button"
          class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700"
          @click="emit('confirm')"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>
