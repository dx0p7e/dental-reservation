import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@spa/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/',                  component: () => import('@spa/views/LandingView.vue') },
    { path: '/login',             component: () => import('@spa/views/LoginView.vue') },
    { path: '/register',          component: () => import('@spa/views/RegisterView.vue') },
    { path: '/doctors',           component: () => import('@spa/views/DoctorsView.vue') },
    { path: '/doctors/:id/slots', component: () => import('@spa/views/DoctorSlotsView.vue') },
    { path: '/services',          component: () => import('@spa/views/ServicesView.vue') },
    { path: '/loyalty',           component: () => import('@spa/views/LoyaltyMarketingView.vue') },
    { path: '/about',             component: () => import('@spa/views/AboutView.vue') },
    { path: '/contact',           component: () => import('@spa/views/ContactView.vue') },
    { path: '/reviews',           component: () => import('@spa/views/ReviewsView.vue') },
    { path: '/privacy',           component: () => import('@spa/views/PrivacyView.vue') },
    { path: '/dashboard/book',              component: () => import('@spa/views/BookView.vue'),              meta: { requiresAuth: true } },
    { path: '/dashboard/appointments',      component: () => import('@spa/views/AppointmentsView.vue'),      meta: { requiresAuth: true } },
    { path: '/dashboard/appointments/:id/reschedule', component: () => import('@spa/views/RescheduleView.vue'), meta: { requiresAuth: true } },
    { path: '/dashboard/request-appointment', component: () => import('@spa/views/RequestBookingView.vue'),  meta: { requiresAuth: true } },
    { path: '/dashboard/loyalty',           component: () => import('@spa/views/LoyaltyDashboardView.vue'),  meta: { requiresAuth: true } },
    { path: '/dashboard/profile',           component: () => import('@spa/views/ProfileView.vue'),           meta: { requiresAuth: true } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
})

export default router
