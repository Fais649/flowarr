import { defineConfig } from 'vitest/config';
import path from 'path';

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
