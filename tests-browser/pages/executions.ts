import type { Page} from '@playwright/test';
import { expect } from '@playwright/test';

export class ExecutionsIndexPage {
    constructor(private page: Page) {}

    async goto() {
        await this.page.goto('/executions');
    }

    async expectVisible() {
        await expect(this.page.locator('body')).toBeAttached();
    }
}
