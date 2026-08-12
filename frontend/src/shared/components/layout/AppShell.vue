<script setup lang="ts">
import { ref } from 'vue'
import FloatingChatBubble from '@/modules/chat/components/FloatingChatBubble.vue'
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'

const sidebarOpen = ref(false)
</script>

<template>
  <div class="min-h-screen">
    <AppHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />

    <div class="mx-auto flex gap-4" :class="$slots.right ? 'max-w-7xl' : 'max-w-5xl'">
      <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <main class="min-w-0 flex-1 px-4 py-6">
        <slot />
      </main>

      <aside v-if="$slots.right" class="hidden w-72 shrink-0 space-y-4 py-6 xl:block">
        <slot name="right" />
      </aside>
    </div>

    <FloatingChatBubble />
  </div>
</template>
