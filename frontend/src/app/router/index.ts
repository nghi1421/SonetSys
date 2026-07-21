import { createRouter, createWebHistory } from 'vue-router'
import { authGuard } from './guards/authGuard'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guest?: boolean
    requiresAdmin?: boolean
    // Admin OR Moderator — for moderation pages (e.g. reports) that the
    // Moderator role is deliberately granted access to, unlike the rest of
    // the /admin panel which stays Admin-only.
    requiresStaff?: boolean
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
      path: '/stories',
      name: 'stories',
      component: () => import('@/modules/stories/views/StoriesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/reels',
      name: 'reels',
      component: () => import('@/modules/reels/views/ReelsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/blocked-users',
      name: 'blocked-users',
      component: () => import('@/modules/follow/views/BlockedUsersView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/hashtag/:tag',
      name: 'hashtag',
      component: () => import('@/modules/feed/views/HashtagView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/search',
      name: 'search',
      component: () => import('@/modules/search/views/SearchView.vue'),
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
      // requiresAdmin is NOT set at the parent level — Reports is deliberately
      // reachable by Moderator too (via its own requiresStaff meta below), so
      // each child route declares its own access requirement instead of
      // inheriting a blanket Admin-only gate.
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/modules/admin/views/AdminDashboardView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'menu',
          name: 'admin-menu',
          component: () => import('@/modules/menu/views/AdminMenuView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'storage',
          name: 'admin-storage-settings',
          component: () => import('@/modules/storage/views/StorageSettingsView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'media',
          name: 'admin-media-library',
          component: () => import('@/modules/storage/views/MediaLibraryView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/modules/users/views/AdminUsersView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'wallets',
          name: 'admin-wallets',
          component: () => import('@/modules/wallet/views/AdminWalletsView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'ads',
          name: 'admin-ads',
          component: () => import('@/modules/advertising/views/AdminAdReviewView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'reports',
          name: 'admin-reports',
          component: () => import('@/modules/report/views/AdminReportsView.vue'),
          meta: { requiresStaff: true },
        },
        {
          path: 'settings',
          name: 'admin-settings',
          component: () => import('@/modules/settings/views/AdminSettingsView.vue'),
          meta: { requiresAdmin: true },
        },
        {
          path: 'reactions',
          name: 'admin-reactions',
          component: () => import('@/modules/reactions/views/AdminReactionsView.vue'),
          meta: { requiresAdmin: true },
        },
      ],
    },
  ],
})

router.beforeEach(authGuard)

export default router
