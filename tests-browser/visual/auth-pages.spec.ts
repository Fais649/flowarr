import { test, expect } from '@playwright/test';

test.describe('auth pages visual regression', () => {
    test('login page', async ({ page, context }) => {
        await context.clearCookies();
        await page.goto('/login');
        await expect(page).toHaveScreenshot('login.png', { fullPage: true });
    });

    test('register page', async ({ page }) => {
        await page.goto('/register');
        await expect(page.locator('body')).toBeAttached();
    });

    test('forgot-password page', async ({ page, context }) => {
        await context.clearCookies();
        await page.goto('/forgot-password');
        await expect(page).toHaveScreenshot('forgot-password.png', { fullPage: true });
    });
});
