// @ts-check
import { test, expect } from '@playwright/test';
import { loginAndOpenShift } from './helpers/login.js';

test.describe('POS V2 — Standard online sale', () => {

    test.beforeEach(async ({ page }) => {
        await loginAndOpenShift(page);
    });

    test('add 2 products, apply line discount, pay single CASH', async ({ page }) => {
        // Wait for catalog to render
        await page.waitForSelector('.product-card', { timeout: 10_000 });

        // Add first 2 visible products
        const cards = await page.locator('button.product-card').all();
        await cards[0].click();
        await cards[1].click();

        // Cart should show 2 items
        const cartItems = page.locator('aside.w-\\[420px\\] .bg-stone-50\\/80');
        await expect(cartItems).toHaveCount(2);

        // Apply a 10% line discount to the first item
        await page.locator('button:has-text("Add discount")').first().click();
        await page.click('button:has-text("Percent")');
        await page.click('button:has-text("1"):not(:has-text("Clear"))');
        await page.click('button:has-text("0"):not(:has-text("Clear"))');
        await page.click('button:has-text("Apply")');

        // Pay
        const payButton = page.locator('button:has-text("Pay $")');
        await expect(payButton).toBeEnabled();
        await payButton.click();

        // Single payment: tap CASH method
        await page.click('button:has-text("Cash")');
        await page.click('button:has-text("Confirm payment")');

        // Flash banner appears
        await expect(page.locator('text=Sale recorded')).toBeVisible({ timeout: 5_000 });

        // Cart should be empty again (new draft)
        await expect(cartItems).toHaveCount(0);
    });

    test('split payment CASH + CARD must equal total', async ({ page }) => {
        await page.waitForSelector('.product-card', { timeout: 10_000 });
        await page.locator('button.product-card').first().click();

        await page.click('button:has-text("Pay $")');
        await page.click('button:has-text("Split")');

        // Add first line CASH $5
        await page.click('button:has-text("Add line")');
        await page.locator('.bg-stone-100 button:has-text("5")').first().click();

        // Confirm should be disabled while not balanced
        const confirm = page.locator('button:has-text("Confirm payment")');
        await expect(confirm).toBeDisabled();
    });
});
