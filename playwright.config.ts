import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests-browser',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 1,
    workers: process.env.CI ? 1 : undefined,
    globalSetup: new URL('./tests-browser/global-setup.ts', import.meta.url).pathname,
    reporter: [
        ['html', { outputFolder: 'playwright-report' }],
        ['list'],
    ],
    use: {
        baseURL: 'http://localhost',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        headless: true,
        storageState: 'tests-browser/fixtures/auth-state.json',
    },
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                contextOptions: {
                    locale: 'en-US',
                },
            },
        },
    ],
});
