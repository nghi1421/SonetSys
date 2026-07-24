<script setup lang="ts">
import type { User } from '@/modules/feed/types'
import { UserRound } from '@lucide/vue'
import { useRouter } from 'vue-router'

interface UsernameProps {
  user: User
}

const route = useRouter()

const props = withDefaults(defineProps<UsernameProps>(), {
  user: () => ({ id: null, name: null, avatar_url: '' }),
})

const onProfile = () => {
  if (props.user.id) {
    route.push({ name: 'user-profile', params: { id: props.user.id } })
  }
}
</script>

<template>
  <h4 class="text-xs font-bold text-cyber-text flex items-center gap-1.5">
    <img
      v-if="user.avatar_url"
      :src="user.avatar_url"
      alt="Avatar"
      class="inline-block h-5 w-5 shrink-0 rounded-full object-cover ring-2 ring-cyber-neon-indigo"
      @click="onProfile"
    />
    <span
      v-else
      class="inline-block h-5 w-5 items-center justify-center rounded-full ring-2 ring-cyber-neon-indigo"
    >
      <UserRound class="h-5 w-5 text-cyber-neon-cyan" />
    </span>
    <router-link
      v-if="user.id"
      :to="`/users/${user.id}`"
      class="transition-colors duration-300 hover:text-cyber-neon-cyan"
    >
      {{ user.name }}
    </router-link>
    <template v-else>{{ user.name }}</template>
  </h4>
</template>
