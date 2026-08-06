<script setup lang="ts">
interface TextareaProps {
  modelValue: string
  label?: string | null
  placeholder?: string | null
  rows?: number
  maxlength?: number | null
  error?: string | null
}

withDefaults(defineProps<TextareaProps>(), {
  label: null,
  placeholder: null,
  rows: 3,
  maxlength: null,
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
      class="block text-xs font-mono uppercase tracking-widest text-cyber-neon-cyan"
    >
      {{ label }}
    </label>
    <textarea
      :id="id"
      :rows="rows"
      :maxlength="maxlength ?? undefined"
      :placeholder="placeholder ?? undefined"
      :value="modelValue"
      class="block w-full resize-none rounded-hud border bg-cyber-glass px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-cyber-bg disabled:cursor-not-allowed disabled:opacity-40"
      :class="
        error
          ? 'border-cyber-neon-pink/50 focus:ring-cyber-neon-pink/60'
          : 'border-cyber-border focus:border-cyber-neon-cyan/50 focus:ring-cyber-neon-indigo/60'
      "
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
    <p v-if="error" class="font-mono text-xs text-cyber-neon-pink">{{ error }}</p>
  </div>
</template>
