import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'url';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            'ziggy-js': fileURLToPath(new URL('./vendor/tightenco/ziggy/dist/index.es.js', import.meta.url)),
        },
    },
    server: {
        host: '127.0.0.1', // Force IPv4 instead of IPv6 [::1]
        strictPort: false,
    },
});
