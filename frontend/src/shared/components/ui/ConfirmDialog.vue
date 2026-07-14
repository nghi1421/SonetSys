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
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-cyber-bg/80 px-4 backdrop-blur-sm"
    @click="emit('cancel')"
  >
    <div class="w-full max-w-sm rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md" @click.stop>
      <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">{{ title }}</h2>
      <p class="mt-2 font-mono text-xs leading-relaxed text-cyber-muted">{{ message }}</p>
      <div class="mt-4 flex justify-end gap-3">
        <button
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          @click="emit('cancel')"
        >
          Cancel
        </button>
        <button
          type="button"
          class="rounded-full border border-cyber-neon-pink/30 bg-cyber-neon-pink/10 px-4 py-1.5 font-mono text-xs font-bold uppercase tracking-wider text-cyber-neon-pink transition-all duration-300 hover:shadow-pink-glow"
          @click="emit('confirm')"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>
