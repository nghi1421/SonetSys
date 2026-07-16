import { createRouter, createWebHistory } from 'vue-router'
import { authGuard } from './guards/authGuard'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guest?: boolean
    requiresAdmin?: boolean
    requiresSuperAdmin?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/pricing',
      name: 'pricing',
      component: () => import('@/modules/billing/views/PricingView.vue'),
    },
    {
      path: '/register-tenant',
      name: 'register-tenant',
      component: () => import('@/modules/billing/views/RegisterTenantView.vue'),
      meta: { guest: true },
    },
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
      path: '/',
      name: 'dashboard',
      component: () => import('@/modules/feed/views/FeedView.vue'),
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
      path: '/groups/:slug',
      name: 'group-detail',
      component: () => import('@/modules/groups/views/GroupDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/menu',
      name: 'admin-menu',
      component: () => import('@/modules/menu/views/AdminMenuView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/storage',
      name: 'admin-storage-settings',
      component: () => import('@/modules/storage/views/StorageSettingsView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/media',
      name: 'admin-media-library',
      component: () => import('@/modules/storage/views/MediaLibraryView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/plans',
      name: 'admin-plans',
      component: () => import('@/modules/billing/views/AdminPlansView.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true },
    },
    {
      path: '/admin/subscription',
      name: 'my-license',
      component: () => import('@/modules/billing/views/SubscriptionView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/tenants',
      name: 'admin-tenants',
      component: () => import('@/modules/billing/views/ManageTenantsView.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true },
    },
  ],
})

router.beforeEach(authGuard)

export default router
