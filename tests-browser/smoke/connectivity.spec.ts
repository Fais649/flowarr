import { test, expect } from '@playwright/test';

test('can connect to the Sail app and the welcome page loads', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBe(200);
    await expect(page.locator('body')).toBeAttached();
});
