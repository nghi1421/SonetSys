<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Moon, Sun } from '@lucide/vue'
import { useThemeStore } from '@/modules/theme/store/themeStore'

const themeStore = useThemeStore()
const { t } = useI18n()

const isLight = computed(() => themeStore.currentTheme === 'light')
const label = computed(() =>
  isLight.value ? t('common.theme.switchToDark') : t('common.theme.switchToLight'),
)
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="isLight"
    :aria-label="label"
    :title="label"
    class="relative inline-flex h-7 w-14 shrink-0 items-center rounded-full border border-cyber-border bg-cyber-glass backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg"
    @click="themeStore.toggleTheme()"
  >
    <span
      class="absolute left-1 flex h-5 w-5 items-center justify-center rounded-full bg-cyber-surface shadow-cyan-glow transition-transform duration-300"
      :class="isLight ? 'translate-x-7 text-cyber-neon-indigo' : 'translate-x-0 text-cyber-neon-cyan'"
    >
      <Sun v-if="isLight" class="h-3 w-3" />
      <Moon v-else class="h-3 w-3" />
    </span>
  </button>
</template>
