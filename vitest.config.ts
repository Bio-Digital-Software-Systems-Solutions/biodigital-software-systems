import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: 'happy-dom',
        setupFiles: ['./vitest.setup.ts'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html', 'lcov'],
            include: [
                'resources/js/**/*.{ts,tsx,js,jsx}',
            ],
            exclude: [
                'node_modules/',
                'vendor/',
                'public/',
                'vitest.setup.ts',
                '**/*.d.ts',
                '**/*.config.*',
                '**/mockData/',
                'tests/',
                '**/__tests__/**',
                '**/*.test.{ts,tsx,js,jsx}',
                '**/*.spec.{ts,tsx,js,jsx}',
            ],
            thresholds: {
                // Achievable thresholds based on current test coverage
                // As more tests are added, these can be increased
                lines: 0.3,
                branches: 29,
                functions: 27,
                statements: 0.3,
            },
        },
        include: ['resources/js/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'],
        exclude: [
            'node_modules',
            'dist',
            '.idea',
            '.git',
            '.cache',
            '**/Pages/Chat/__tests__/Index.test.tsx', // TODO: Convert from Jest to Vitest
        ],
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
