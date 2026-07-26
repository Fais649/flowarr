import type { Page } from '@playwright/test';

export async function loginAs(page: Page, email = 'test@example.com', password = 'password') {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/dashboard/);
}

export async function loginAndSaveState(page: Page, email = 'test@example.com', password = 'password') {
    await loginAs(page, email, password);
    await page.context().storageState({ path: 'tests-browser/fixtures/auth-state.json' });
}
