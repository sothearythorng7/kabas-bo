import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router.js';
import { db } from './db/index.js';
import '../../css/pos-v2.css';

async function boot() {
    await db.open();

    const app = createApp(App);
    app.use(createPinia());
    app.use(router);
    app.mount('#pos-v2-app');
}

boot().catch((err) => {
    console.error('[POS V2] Boot failed', err);
    const el = document.getElementById('pos-v2-app');
    if (el) {
        el.innerHTML = `<div style="padding:2rem;font-family:system-ui">
            <h1 style="color:#b91c1c">POS V2 boot error</h1>
            <pre style="background:#fef2f2;padding:1rem;border-radius:8px;overflow:auto">${err.message}</pre>
        </div>`;
    }
});
