import { test, expect } from '@playwright/test';

test('executions index page renders', async ({ page }) => {
    await page.goto('/executions');
    await expect(page.locator('body')).toBeAttached();
});
