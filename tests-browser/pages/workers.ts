import type { Page} from '@playwright/test';
import { expect } from '@playwright/test';

export class WorkersIndexPage {
    constructor(private page: Page) {}

    async goto() {
        await this.page.goto('/workers');
    }

    async expectVisible() {
        await expect(this.page.locator('body')).toBeAttached();
    }

    async expectAddWorkerButton() {
        await expect(this.page.getByRole('button', { name: /add worker/i })).toBeVisible();
    }

    async expectBulkActions() {
        await expect(this.page.getByRole('button', { name: /start all/i })).toBeVisible();
        await expect(this.page.getByRole('button', { name: /pause all/i })).toBeVisible();
        await expect(this.page.getByRole('button', { name: /resume all/i })).toBeVisible();
        await expect(this.page.getByRole('button', { name: /stop all/i })).toBeVisible();
    }
}
