import { test, expect } from '@playwright/test';

test('dashboard loads without console errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    await page.goto('/dashboard');
    await expect(page.locator('body')).toBeAttached();

    expect(errors).toHaveLength(0);
});

test('dashboard sidebar shows navigation sections', async ({ page }) => {
    await page.goto('/dashboard');
    const sidebar = page.locator('[data-slot="sidebar"]');
    await expect(sidebar.getByRole('link', { name: /Dashboard/i })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Libraries/i })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Executions/i })).toBeVisible();
    await expect(sidebar.getByRole('link', { name: /Workers/i })).toBeVisible();
});
