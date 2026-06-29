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
    resolve: {
        // Guarantee a single React instance in the bundle to avoid
        // "Invalid hook call" (React error #321) from duplicate copies.
        dedupe: ['react', 'react-dom'],
    },
    build: {
        rollupOptions: {
            output: {
                // Force the whole React runtime into one vendor chunk so every
                // page/component shares the same React module instance with a
                // correct initialization order. Without this, Rollup split a
                // second `react.esm` chunk that resolved to `null` inside lazily
                // imported page chunks, breaking hooks (React error #321).
                manualChunks(id) {
                    if (
                        /node_modules\/(react|react-dom|react-is|scheduler|use-sync-external-store|@inertiajs\/react|@inertiajs\/core|react-i18next|i18next)\//.test(id)
                    ) {
                        return 'react-vendor';
                    }
                },
            },
        },
    },
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
            // Poll less aggressively: at 300ms over the slow macOS bind mount the
            // single Node thread saturates polling thousands of files, starving the
            // event loop until Vite stops answering requests (blank page).
            interval: 1000,
            binaryInterval: 1500,
            // Never poll heavy or constantly-churning directories. The browser-logs
            // file under .playwright-mcp is rewritten on every interaction, which
            // otherwise triggers endless full reloads and polling churn.
            ignored: [
                '**/node_modules/**',
                '**/vendor/**',
                '**/.git/**',
                '**/storage/**',
                '**/public/build/**',
                '**/.claude/**',
                '**/.playwright-mcp/**',
                '**/*.{jpg,jpeg,png,gif,webp,mp4}',
            ],
        },
    },
});
