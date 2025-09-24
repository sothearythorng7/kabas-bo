import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router.js';
import { syncUsers } from './db.js';
import { startSyncWorker } from './syncWorker.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.mount('#app');

await syncUsers();
startSyncWorker();
