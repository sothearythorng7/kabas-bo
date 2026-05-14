import { createRouter, createWebHistory } from 'vue-router';
import { useSessionStore } from './stores/session.js';

const routes = [
    {
        path: '/pos-v2',
        redirect: '/pos-v2/login',
    },
    {
        path: '/pos-v2/login',
        name: 'login',
        component: () => import('./views/LoginView.vue'),
        meta: { public: true },
    },
    {
        path: '/pos-v2/shift/start',
        name: 'shift-start',
        component: () => import('./views/ShiftStartView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/pos-v2/shift/end',
        name: 'shift-end',
        component: () => import('./views/ShiftEndView.vue'),
        meta: { requiresAuth: true, requiresShift: true },
    },
    {
        path: '/pos-v2/dashboard',
        name: 'dashboard',
        component: () => import('./views/DashboardView.vue'),
        meta: { requiresAuth: true, requiresShift: true },
    },
    {
        path: '/pos-v2/journal',
        name: 'journal',
        component: () => import('./views/JournalView.vue'),
        meta: { requiresAuth: true, requiresShift: true },
    },
    {
        path: '/pos-v2/sale/:id',
        name: 'sale-detail',
        component: () => import('./views/SaleDetailView.vue'),
        meta: { requiresAuth: true, requiresShift: true },
    },
    {
        path: '/pos-v2/planning',
        name: 'planning',
        component: () => import('./views/MyPlanningView.vue'),
        meta: { requiresAuth: true, requiresShift: true },
    },
    {
        path: '/pos-v2/leave-request',
        name: 'leave-request',
        component: () => import('./views/LeaveRequestView.vue'),
        meta: { requiresAuth: true, requiresShift: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const session = useSessionStore();
    if (to.meta.requiresAuth && !session.isAuthenticated) {
        return { name: 'login' };
    }
    if (to.meta.requiresShift && !session.hasOpenShift) {
        return { name: 'shift-start' };
    }
});

export default router;
