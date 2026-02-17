import { defineConfig } from 'vitest/config';

export default defineConfig({
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        include: ['resources/js/tests/**/*.spec.ts'],
    },
});
