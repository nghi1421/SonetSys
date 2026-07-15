<script setup lang="ts">
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'
</script>

<template>
  <div class="min-h-screen">
    <AppHeader />

    <div class="mx-auto flex max-w-5xl">
      <AppSidebar />

      <main class="min-w-0 flex-1 px-4 py-6">
        <slot />
      </main>
    </div>
  </div>
</template>
