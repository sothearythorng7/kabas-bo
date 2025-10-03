import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // nécessaire pour Vue 3

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                'resources/sass/app.scss',       // BO
                'resources/js/app.js',           // BO
                'resources/js/menu.js',
                //'resources/js/pos/main.js',      // POS SPA
            ],
            refresh: true,
        }),
    ],
});
