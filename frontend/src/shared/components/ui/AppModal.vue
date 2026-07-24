<script setup lang="ts">
interface ModalProps {
  open: boolean
  size?: 'sm' | 'md' | 'lg' | 'xl'
  variant?: 'center' | 'sheet'
  bare?: boolean
  closeOnBackdrop?: boolean
  panelClass?: string
}

const props = withDefaults(defineProps<ModalProps>(), {
  size: 'md',
  variant: 'center',
  bare: false,
  closeOnBackdrop: true,
  panelClass: '',
})

const emit = defineEmits<{ close: [] }>()

const SIZE_CLASS: Record<NonNullable<ModalProps['size']>, string> = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
}

function onBackdropClick(): void {
  if (props.closeOnBackdrop) emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex justify-center px-4 backdrop-blur-sm"
      :class="variant === 'sheet' ? 'items-end sm:items-center' : 'items-center py-8'"
      @click="onBackdropClick"
    >
      <div class="absolute inset-0 bg-cyber-bg/80" />

      <slot name="before" />

      <div
        class="relative max-h-[85vh] w-full overflow-y-auto"
        :class="[
          SIZE_CLASS[size],
          bare
            ? 'rounded-hud'
            : 'rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md',
          variant === 'sheet' && !bare ? 'rounded-t-hud sm:rounded-hud' : '',
          panelClass,
        ]"
        @click.stop
      >
        <slot />
      </div>
    </div>
  </Teleport>
</template>
