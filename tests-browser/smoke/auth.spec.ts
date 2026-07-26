import { test, expect } from '@playwright/test';

test('login page renders the login form', async ({ page, context }) => {
    await context.clearCookies();
    await page.goto('/login');

    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();

    expect(errors).toHaveLength(0);
});

test('register page loads', async ({ page }) => {
    await page.goto('/register');
    await expect(page.locator('body')).toBeAttached();
});

test('forgot-password page renders the email input', async ({ page, context }) => {
    await context.clearCookies();
    await page.goto('/forgot-password');

    await expect(page.locator('input[type="email"]')).toBeVisible();
});
