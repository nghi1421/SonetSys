<script setup lang="ts">
interface SelectProps {
  modelValue: string
  label?: string | null
  error?: string | null
}

withDefaults(defineProps<SelectProps>(), {
  label: null,
  error: null,
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const id = `field-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <div class="space-y-1.5">
    <label
      v-if="label"
      :for="id"
 class="block text-xs text-cyber-neon-cyan"
    >
      {{ label }}
    </label>
    <select
      :id="id"
      :value="modelValue"
 class="block w-full rounded-hud border bg-cyber-glass px-3 py-2 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40"
      :class="
        error
          ? 'border-cyber-neon-pink/50 focus:ring-cyber-neon-pink/60'
          : 'border-cyber-border focus:border-cyber-neon-cyan/50 focus:ring-cyber-neon-indigo/60'
      "
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <slot />
    </select>
 <p v-if="error" class="text-xs text-cyber-neon-pink">{{ error }}</p>
  </div>
</template>
