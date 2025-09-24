import { createRouter, createWebHistory } from 'vue-router';
import Login from './components/Login.vue';
import Products from './components/Products.vue';
import ShiftStart from './components/ShiftStart.vue';
import ShiftEnd from './components/ShiftEnd.vue';
import Layout from './components/Layout.vue';

const routes = [
  { path: '/', component: Login },
  { path: '/shift/start', component: ShiftStart },
  {
    path: '/',
    component: Layout,
    children: [
      { path: '/products', component: Products },
      { path: '/shift/end', component: ShiftEnd },
    ]
  }
];

export default createRouter({
  history: createWebHistory('/pos'),
  routes
});
