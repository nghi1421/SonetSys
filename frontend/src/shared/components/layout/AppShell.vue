<script setup lang="ts">
import { ref } from 'vue'
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'

const sidebarOpen = ref(false)
</script>

<template>
  <div class="min-h-screen">
    <AppHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />

    <div class="mx-auto flex max-w-5xl">
      <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <main class="min-w-0 flex-1 px-4 py-6">
        <slot />
      </main>
    </div>
  </div>
</template>
