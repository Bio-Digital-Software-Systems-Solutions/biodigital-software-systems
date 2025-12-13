import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '0.0.0.0', // Listen on all interfaces for Docker
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost', // Use localhost for HMR URLs in browser
            port: 5173,
        },
    },
});
