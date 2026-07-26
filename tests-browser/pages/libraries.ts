import type { Page} from '@playwright/test';
import { expect } from '@playwright/test';

export class LibrariesIndexPage {
    constructor(private page: Page) {}

    async goto() {
        await this.page.goto('/libraries');
    }

    async expectVisible() {
        await expect(this.page.locator('body')).toBeAttached();
    }
}

export class LibrariesCreatePage {
    constructor(private page: Page) {}

    async goto() {
        await this.page.goto('/libraries/create');
    }

    async expectVisible() {
        await expect(this.page.locator('input[name="base_path"]')).toBeVisible();
        await expect(this.page.locator('input[name="scan_interval"]')).toBeVisible();
    }
}
