import { createRouter, createWebHistory } from 'vue-router'
import { authGuard } from './guards/authGuard'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guest?: boolean
    requiresAdmin?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/modules/auth/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/modules/auth/views/RegisterView.vue'),
      meta: { guest: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/modules/auth/views/ForgotPasswordView.vue'),
      meta: { guest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/modules/auth/views/ResetPasswordView.vue'),
    },
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/modules/feed/views/FeedView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/posts/:id',
      name: 'post-detail',
      component: () => import('@/modules/feed/views/PostDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/users/:id',
      name: 'user-profile',
      component: () => import('@/modules/follow/views/UserProfileView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/m/:slug',
      name: 'menu-page',
      component: () => import('@/modules/menu/views/MenuPageView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/groups',
      name: 'groups-list',
      component: () => import('@/modules/groups/views/GroupsListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/wallet',
      name: 'wallet',
      component: () => import('@/modules/wallet/views/WalletHistoryView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/messages',
      name: 'messages',
      component: () => import('@/modules/chat/views/MessagesListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/messages/:conversationId',
      name: 'conversation',
      component: () => import('@/modules/chat/views/ConversationView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/advertise',
      name: 'advertise',
      component: () => import('@/modules/advertising/views/AdvertiseView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/groups/:slug',
      name: 'group-detail',
      component: () => import('@/modules/groups/views/GroupDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/admin',
      component: () => import('@/shared/components/layout/AdminShell.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/modules/admin/views/AdminDashboardView.vue'),
        },
        {
          path: 'menu',
          name: 'admin-menu',
          component: () => import('@/modules/menu/views/AdminMenuView.vue'),
        },
        {
          path: 'storage',
          name: 'admin-storage-settings',
          component: () => import('@/modules/storage/views/StorageSettingsView.vue'),
        },
        {
          path: 'media',
          name: 'admin-media-library',
          component: () => import('@/modules/storage/views/MediaLibraryView.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/modules/users/views/AdminUsersView.vue'),
        },
        {
          path: 'wallets',
          name: 'admin-wallets',
          component: () => import('@/modules/wallet/views/AdminWalletsView.vue'),
        },
        {
          path: 'ads',
          name: 'admin-ads',
          component: () => import('@/modules/advertising/views/AdminAdReviewView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach(authGuard)

export default router
