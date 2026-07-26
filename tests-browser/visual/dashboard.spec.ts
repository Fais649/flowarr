import { test, expect } from '@playwright/test';

test('dashboard visual regression', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveScreenshot('dashboard.png', { fullPage: true });
});
