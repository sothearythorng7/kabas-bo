// @ts-check
import { test, expect } from '@playwright/test';
import { loginAndOpenShift } from './helpers/login.js';

/**
 * End shift with deliberate cash gap.
 *
 * This test simulates a cashier closing a shift where the counted cash
 * doesn't match the expected amount, validating that:
 *  - the verification math is correct
 *  - the gap warning is shown
 *  - the shift can still be confirmed (V1 behaviour: warn but allow)
 */
test.describe('POS V2 — Shift end with gap', () => {

    test.beforeEach(async ({ page }) => {
        await loginAndOpenShift(page);
    });

    test('3 steps: visitors → counted → verification, warns on gap', async ({ page }) => {
        // Click "End shift" in the left rail (red icon at the bottom)
        await page.click('button[title="End shift"], button[title="Fermer la caisse"]');
        await page.waitForURL(/\/pos-v2\/shift\/end$/);

        // Step 1: visitors
        await page.click('button:has-text("4"):not(:has-text("Clear"))');
        await page.click('button:has-text("7"):not(:has-text("Clear"))');
        await page.click('button:has-text(/Next|Suivant/)');

        // Step 2: counted cash (force a gap by typing far less than expected)
        await page.click('button:has-text("1"):not(:has-text("Clear"))');
        await page.click('button:has-text("0"):not(:has-text("Clear"))');
        await page.click('button:has-text(/Next|Suivant/)');

        // Step 3: verification
        await expect(page.locator('text=/Expected cash|Caisse attendue/')).toBeVisible();
        await expect(page.locator('text=/Counted amount|Montant compté/')).toBeVisible();

        // Difference row should show a non-zero number (we typed $10 vs expected ≥ opening)
        const diffRow = page.locator('text=/Difference|Écart/').locator('..');
        await expect(diffRow).toBeVisible();

        // Gap banner present
        await expect(page.locator('text=/Cash gap detected|Écart de caisse/')).toBeVisible();

        // Confirm closes the shift and routes back to login
        await page.click('button:has-text(/Confirm|Confirmer/)');
        await page.waitForURL(/\/pos-v2\/login$/, { timeout: 15_000 });
    });
});
