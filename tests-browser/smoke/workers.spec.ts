import { test, expect } from '@playwright/test';
import { WorkersIndexPage } from '../pages/workers';

test('workers index page renders', async ({ page }) => {
    const workersPage = new WorkersIndexPage(page);
    await workersPage.goto();
    await workersPage.expectVisible();
});

test('workers page has add worker button and bulk actions', async ({ page }) => {
    const workersPage = new WorkersIndexPage(page);
    await workersPage.goto();

    await workersPage.expectAddWorkerButton();
    await workersPage.expectBulkActions();
});

test('workers page has no console errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    await page.goto('/workers');
    await expect(page.locator('body')).toBeAttached();

    expect(errors).toHaveLength(0);
});
