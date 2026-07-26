import { Page, expect } from '@playwright/test';

export class LoginPage {
    constructor(private page: Page) {}

    async goto() {
        await this.page.goto('/login');
    }

    async fillEmail(email: string) {
        await this.page.fill('input[name="email"]', email);
    }

    async fillPassword(password: string) {
        await this.page.fill('input[name="password"]', password);
    }

    async submit() {
        await this.page.click('button[type="submit"]');
    }

    async loginAs(email: string, password: string) {
        await this.goto();
        await this.fillEmail(email);
        await this.fillPassword(password);
        await this.submit();
        await this.page.waitForURL(/\/dashboard/);
    }

    async expectVisible() {
        await expect(this.page.locator('input[name="email"]')).toBeVisible();
        await expect(this.page.locator('input[name="password"]')).toBeVisible();
    }
}
