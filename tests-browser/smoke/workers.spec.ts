import { test, expect } from '@playwright/test';

test('workers index page renders', async ({ page }) => {
    await page.goto('/workers');
    await expect(page.locator('body')).toBeAttached();
});
