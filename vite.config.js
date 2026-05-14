import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // nécessaire pour Vue 3
import tailwindcss from '@tailwindcss/vite'; // pour POS V2 uniquement (scope via @import dans pos-v2.css)

export default defineConfig({
    plugins: [
        vue(),
        tailwindcss(),
        laravel({
            input: [
                'resources/sass/app.scss',       // BO
                'resources/js/app.js',           // BO
                'resources/js/menu.js',
                //'resources/js/pos/main.js',      // POS V1 SPA (dormant)
                'resources/js/pos-v2/main.js',   // POS V2 SPA (Vue 3)
            ],
            refresh: true,
        }),
    ],
});
