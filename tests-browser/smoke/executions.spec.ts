import { test, expect } from '@playwright/test';
import { ExecutionsIndexPage } from '../pages/executions';

test('executions index page renders', async ({ page }) => {
    const executionsPage = new ExecutionsIndexPage(page);
    await executionsPage.goto();
    await executionsPage.expectVisible();
});

test('executions index page has no console errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    await page.goto('/executions');
    await expect(page.locator('body')).toBeAttached();

    expect(errors).toHaveLength(0);
});
