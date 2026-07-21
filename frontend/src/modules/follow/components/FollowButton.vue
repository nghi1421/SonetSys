<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useFollowStore } from '../store/followStore'

const props = defineProps<{ userId: number; isFollowing: boolean }>()

const authStore = useAuthStore()
const followStore = useFollowStore()
const { t } = useI18n()

const localIsFollowing = ref(props.isFollowing)
const loading = ref(false)

watch(
  () => props.isFollowing,
  (value) => {
    localIsFollowing.value = value
  },
)

async function toggle(): Promise<void> {
  loading.value = true
  try {
    if (localIsFollowing.value) {
      await followStore.unfollow(props.userId)
      localIsFollowing.value = false
    } else {
      await followStore.follow(props.userId)
      localIsFollowing.value = true
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AppButton
    v-if="userId !== authStore.user?.id"
    :label="localIsFollowing ? t('follow.unfollow') : t('follow.follow')"
    :variant="localIsFollowing ? 'secondary' : 'primary'"
    :loading="loading"
    @click="toggle"
  />
</template>
