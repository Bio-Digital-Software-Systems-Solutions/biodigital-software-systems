import type { KnipConfig } from 'knip';

const config: KnipConfig = {
    entry: ['resources/js/app.tsx', 'resources/js/Pages/**/*.tsx'],
    project: ['resources/js/**/*.{ts,tsx}'],
    ignore: [
        'resources/js/**/*.test.{ts,tsx}',
        'resources/js/**/__tests__/**',
        'resources/js/types/**',
    ],
};

export default config;
