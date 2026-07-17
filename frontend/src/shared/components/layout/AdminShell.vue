<script setup lang="ts">
import { ref } from 'vue'
import AdminHeader from './AdminHeader.vue'
import AdminSidebar from './AdminSidebar.vue'

const sidebarOpen = ref(false)
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <AdminHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />

    <div class="flex">
      <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <main class="min-w-0 flex-1 px-4 py-6 sm:px-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
