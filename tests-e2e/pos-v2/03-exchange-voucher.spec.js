// @ts-check
import { test, expect } from '@playwright/test';
import { loginAndOpenShift } from './helpers/login.js';

/**
 * Exchange with voucher generation.
 *
 * Requires a past sale in kabas_dev that is:
 *  - Less than 30 days old
 *  - Has at least 2 items not yet exchanged
 *
 * Pass its ID via env: TEST_EXCHANGEABLE_SALE_ID=12345
 * Skip if not provided so the test isn't a false negative in CI.
 */
const SALE_ID = process.env.TEST_EXCHANGEABLE_SALE_ID;

test.describe('POS V2 — Exchange flow', () => {

    test.skip(!SALE_ID, 'set TEST_EXCHANGEABLE_SALE_ID env var to a real exchangeable sale');

    test.beforeEach(async ({ page }) => {
        await loginAndOpenShift(page);
    });

    test('lookup → return → new (less than return) → auto voucher', async ({ page }) => {
        // Open exchange wizard
        await page.click('button:has-text("Exchange")');
        await expect(page.locator('text=/Look up sale|Rechercher une vente/')).toBeVisible();

        // Type sale ID, validate
        await page.fill('input[placeholder*="12345"]', SALE_ID);
        await page.click('button:has-text(/Look up|Valid/)');

        // Should be on return step
        await expect(page.locator('text=Select items to return, text=Articles à retourner')).toBeVisible({ timeout: 10_000 });

        // Tick first 2 items
        const checks = page.locator('input[type=checkbox]');
        await checks.nth(0).check();
        await checks.nth(1).check();

        // Move to new items step without picking any new items → balance should be credit
        await page.click('button:has-text(/Next|Suivant/)');

        // Footer should show "credit → auto voucher"
        await expect(page.locator('text=/auto voucher|Un bon d\'achat/')).toBeVisible();

        // Submit (no payment step since balance is credit-side)
        await page.click('button:has-text(/Process exchange|Valider l\'échange/)');

        // Done step shows voucher generated
        await expect(page.locator('text=/Exchange recorded|Échange enregistré/')).toBeVisible({ timeout: 10_000 });
        await expect(page.locator('text=/Voucher generated|Bon d\'achat émis/')).toBeVisible();
    });

    test('not_found error', async ({ page }) => {
        await page.click('button:has-text("Exchange")');
        await page.fill('input[placeholder*="12345"]', '99999999');
        await page.click('button:has-text(/Look up|Valid/)');
        await expect(page.locator('text=/Sale not found|Vente introuvable/')).toBeVisible({ timeout: 10_000 });
    });
});
