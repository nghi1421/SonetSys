<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AppModal from './AppModal.vue'

interface ConfirmDialogProps {
  open: boolean
  title: string
  message: string
}

defineProps<ConfirmDialogProps>()

const emit = defineEmits<{ confirm: []; cancel: [] }>()
const { t } = useI18n()
</script>

<template>
  <AppModal :open="open" size="sm" @close="emit('cancel')">
 <h2 class="text-xs font-bold text-cyber-text">
      {{ title }}
    </h2>
 <p class="mt-2 text-xs leading-relaxed text-cyber-muted">{{ message }}</p>
    <div class="mt-4 flex justify-end gap-3">
      <button
        type="button"
 class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
        @click="emit('cancel')"
      >
        {{ t('common.cancel') }}
      </button>
      <button
        type="button"
 class="rounded-full border border-cyber-neon-pink/30 bg-cyber-neon-pink/10 px-4 py-1.5 text-xs font-bold text-cyber-neon-pink transition-all duration-300 hover:shadow-pink-glow"
        @click="emit('confirm')"
      >
        {{ t('common.delete') }}
      </button>
    </div>
  </AppModal>
</template>
