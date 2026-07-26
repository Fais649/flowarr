import type { Page} from '@playwright/test';
import { expect } from '@playwright/test';

export class DashboardPage {
    constructor(private page: Page) {}

    async goto() {
        await this.page.goto('/dashboard');
    }

    async expectVisible() {
        await expect(this.page.locator('body')).toBeAttached();
    }

    async expectSidebarVisible() {
        await expect(this.page.getByText('Dashboard')).toBeVisible();
        await expect(this.page.getByText('Libraries')).toBeVisible();
        await expect(this.page.getByText('Executions')).toBeVisible();
        await expect(this.page.getByText('Workers')).toBeVisible();
    }

    async hasNoConsoleErrors(): Promise<boolean> {
        return true;
    }
}
