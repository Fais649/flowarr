import { test, expect } from '@playwright/test';

test('executions index visual regression', async ({ page }) => {
    await page.goto('/executions');
    await expect(page).toHaveScreenshot('executions-index.png', { fullPage: true });
});
