import { test, expect } from '@playwright/test';

test.use({ storageState: undefined });

test('welcome page loads without console errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    await page.goto('/');
    await expect(page.locator('body')).toBeAttached();

    expect(errors).toHaveLength(0);
});
