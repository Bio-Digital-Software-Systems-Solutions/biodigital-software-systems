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
        watch: {
            // Native fs events don't cross the macOS host -> Linux container
            // bind mount, so poll for changes to keep HMR/watch working.
            usePolling: true,
            interval: 300,
        },
    },
});
