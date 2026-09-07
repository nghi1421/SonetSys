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

    <div class="flex gap-4 pr-4 sm:pr-6">
      <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <div class="flex min-w-0 flex-1 gap-4" :class="$slots.right && 'justify-center'">
        <main class="min-w-0 px-4 py-6" :class="!$slots.right && 'flex-1'">
          <slot />
        </main>

        <aside v-if="$slots.right" class="hidden w-96 shrink-0 space-y-4 py-6 xl:block">
          <slot name="right" />
        </aside>
      </div>
    </div>

    <FloatingChatBubble />
  </div>
</template>
