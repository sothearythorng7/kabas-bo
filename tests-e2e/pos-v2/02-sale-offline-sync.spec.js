// @ts-check
import { test, expect } from '@playwright/test';
import { loginAndOpenShift } from './helpers/login.js';

test.describe('POS V2 — Offline sale + reconnect sync', () => {

    test.beforeEach(async ({ page }) => {
        await loginAndOpenShift(page);
    });

    test('finalize while offline, sync after reconnect', async ({ page, context }) => {
        await page.waitForSelector('.product-card', { timeout: 10_000 });

        // Go offline
        await context.setOffline(true);
        await expect(page.locator('text=Offline')).toBeVisible({ timeout: 5_000 });

        // Make a sale
        await page.locator('button.product-card').first().click();
        await page.click('button:has-text("Pay $")');
        await page.click('button:has-text("Cash")');
        await page.click('button:has-text("Confirm payment")');

        await expect(page.locator('text=Sale recorded')).toBeVisible();

        // Pending badge should be 1
        const badge = page.locator('button:has(svg) >> text=Sync now ~ span').first();
        // Sync should NOT have happened because offline
        await expect(page.locator('button:has-text("Sync") .bg-amber-100')).toBeVisible({ timeout: 5_000 });

        // Reconnect
        await context.setOffline(false);

        // Within a few seconds the pending badge should disappear
        await expect(page.locator('button:has-text("Sync") .bg-amber-100')).toBeHidden({ timeout: 65_000 });
    });
});
