import { chromium } from '@playwright/test';

async function globalSetup() {
    const browser = await chromium.launch();
    const page = await browser.newPage();
    await page.goto('http://localhost/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/dashboard/);
    await page.context().storageState({ path: 'tests-browser/fixtures/auth-state.json' });
    await browser.close();
}

export default globalSetup;
