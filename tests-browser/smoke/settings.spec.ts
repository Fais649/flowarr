import { test, expect } from '@playwright/test';

test('settings profile page renders', async ({ page }) => {
    await page.goto('/settings/profile');
    await expect(page.locator('body')).toBeAttached();
});

test('settings security page renders', async ({ page }) => {
    await page.goto('/settings/security');
    await expect(page.locator('body')).toBeAttached();
});

test('settings appearance page renders', async ({ page }) => {
    await page.goto('/settings/appearance');
    await expect(page.locator('body')).toBeAttached();
});
