import { test, expect } from '@playwright/test';

test('libraries index visual regression', async ({ page }) => {
    await page.goto('/libraries');
    await expect(page).toHaveScreenshot('libraries-index.png', { fullPage: true });
});

test('libraries create visual regression', async ({ page }) => {
    await page.goto('/libraries/create');
    await expect(page).toHaveScreenshot('libraries-create.png', { fullPage: true });
});
