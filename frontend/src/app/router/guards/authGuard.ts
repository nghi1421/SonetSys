import type { NavigationGuardWithThis } from 'vue-router'
import { useAuthStore } from '@/modules/auth/store/authStore'

export const authGuard: NavigationGuardWithThis<undefined> = (to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAdmin && authStore.user?.role.slug !== 'admin') {
    return { name: 'dashboard' }
  }

  return true
}
