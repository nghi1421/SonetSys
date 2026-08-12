<script setup lang="ts">
interface InputProps {
  modelValue: string
  label: string
  type?: string
  autocomplete?: string
  error?: string | null
}

withDefaults(defineProps<InputProps>(), {
  type: 'text',
  autocomplete: 'off',
  error: null,
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const id = `field-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <div class="space-y-1.5">
    <label :for="id" class="block text-xs font-mono uppercase tracking-widest text-cyber-neon-cyan">
      {{ label }}
    </label>
    <input
      :id="id"
      :type="type"
      :autocomplete="autocomplete"
      :value="modelValue"
      class="block w-full rounded-hud border bg-cyber-glass px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40"
      :class="
        error
          ? 'border-cyber-neon-pink/50 focus:ring-cyber-neon-pink/60'
          : 'border-cyber-border focus:border-cyber-neon-cyan/50 focus:ring-cyber-neon-indigo/60'
      "
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" class="font-mono text-xs text-cyber-neon-pink">{{ error }}</p>
  </div>
</template>
