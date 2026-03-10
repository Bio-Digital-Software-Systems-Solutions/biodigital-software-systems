import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import prettier from 'eslint-config-prettier';

export default tseslint.config(
    // Global ignores
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/**',
            'storage/**',
            '*.config.js',
            '*.config.cjs',
            '*.config.mjs',
            'vite.config.ts',
            'vitest.config.ts',
            'tailwind.config.js',
        ],
    },

    // Base JS rules
    js.configs.recommended,

    // TypeScript rules
    ...tseslint.configs.recommended,

    // React rules
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        plugins: {
            react,
            'react-hooks': reactHooks,
        },
        languageOptions: {
            globals: {
                ...globals.browser,
            },
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
            },
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
        rules: {
            // React
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'warn',

            // React Hooks
            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'warn',

            // TypeScript
            '@typescript-eslint/no-unused-vars': ['warn', {
                argsIgnorePattern: '^_',
                varsIgnorePattern: '^_',
            }],
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/no-empty-object-type': 'off',
            '@typescript-eslint/no-require-imports': 'off',

            // Handled by TypeScript
            'no-undef': 'off',
        },
    },

    // Test files - more relaxed rules
    {
        files: ['**/*.test.{ts,tsx}', '**/__tests__/**'],
        rules: {
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },

    // Prettier must be last to override conflicting rules
    prettier,
);
