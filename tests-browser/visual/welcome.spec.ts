import { test, expect } from '@playwright/test';

test('welcome page visual regression', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveScreenshot('welcome.png', { fullPage: true });
});
