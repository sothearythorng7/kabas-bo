import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/pos-v2',
        redirect: '/pos-v2/login',
    },
    {
        path: '/pos-v2/login',
        name: 'login',
        component: () => import('./views/LoginView.vue'),
        meta: { layout: 'fullscreen' },
    },
    {
        path: '/pos-v2/dashboard',
        name: 'dashboard',
        component: () => import('./views/DashboardView.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
