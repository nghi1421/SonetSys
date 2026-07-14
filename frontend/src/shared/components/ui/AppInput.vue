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
    <label :for="id" class="block text-sm font-medium text-slate-700 dark:text-zinc-300">
      {{ label }}
    </label>
    <input
      :id="id"
      :type="type"
      :autocomplete="autocomplete"
      :value="modelValue"
      class="block w-full rounded-md border px-3 py-2 text-sm text-slate-900 transition-colors duration-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed dark:text-zinc-100 dark:placeholder:text-zinc-500"
      :class="
        error
          ? 'border-red-300 focus:ring-red-500 dark:border-red-800'
          : 'border-zinc-200 focus:ring-accent-500 dark:border-zinc-700 dark:bg-zinc-800'
      "
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>
