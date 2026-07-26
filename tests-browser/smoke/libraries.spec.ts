import { test, expect } from '@playwright/test';

test('libraries index page renders', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    await page.goto('/libraries');
    await expect(page.locator('body')).toBeAttached();

    expect(errors).toHaveLength(0);
});

test('libraries create page renders the form', async ({ page }) => {
    await page.goto('/libraries/create');
    await expect(page.locator('#base_path')).toBeVisible();
    await expect(page.locator('#scan_interval')).toBeVisible();
});
