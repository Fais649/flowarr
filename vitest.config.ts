import path from 'path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['./tests-frontend/setup.ts'],
        css: false,
        include: ['tests-frontend/**/*.{test,spec}.{ts,tsx}'],
    },
});
