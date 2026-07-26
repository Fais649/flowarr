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
}
