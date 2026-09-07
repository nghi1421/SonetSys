<script setup lang="ts">
import { computed } from 'vue'
import { SUPPORTED_LOCALES } from '@/i18n'
import type { SupportedLocale } from '@/i18n'
import { useLocaleStore } from '@/modules/locale/store/localeStore'

withDefaults(defineProps<{ variant?: 'dark' | 'light' }>(), { variant: 'dark' })

const LOCALE_LABELS: Record<SupportedLocale, string> = {
  en: 'English',
}

const localeStore = useLocaleStore()

const showSwitcher = computed(() => SUPPORTED_LOCALES.length > 1)

function onChange(event: Event): void {
  localeStore.setLocale((event.target as HTMLSelectElement).value as SupportedLocale)
}
</script>

<template>
  <select
    v-if="showSwitcher"
    :value="localeStore.currentLocale"
 class="rounded-full border px-2.5 py-1 text-xs font-medium focus:outline-none focus:ring-2"
    :class="
      variant === 'dark'
        ? 'border-cyber-border bg-cyber-glass text-cyber-muted backdrop-blur-md focus:ring-cyber-neon-indigo/60'
        : 'border-slate-200 bg-white text-slate-600 focus:ring-blue-600/40'
    "
    @change="onChange"
  >
    <option v-for="locale in SUPPORTED_LOCALES" :key="locale" :value="locale">
      {{ LOCALE_LABELS[locale] }}
    </option>
  </select>
</template>
