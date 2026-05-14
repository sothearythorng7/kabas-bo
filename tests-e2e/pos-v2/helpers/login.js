/**
 * Reusable login helper for POS V2 E2E tests.
 *
 * Picks PIN digits one at a time on the on-screen PinPad. The PinPad
 * auto-submits when the 6th digit is pressed, so we don't click "Sign in".
 *
 * Set TEST_PIN in your env (default '123456') matching a seeded user in
 * `kabas_dev`. Default user_id is the test admin Mickaël seeded:
 * `claude-seo@kabas.local` (user_id=24) — but any POS user with PIN works.
 */
export async function loginWithPin(page, pin = process.env.TEST_PIN || '123456') {
    await page.goto('/pos-v2/login');
    await page.waitForSelector('text=Enter your PIN, text=Saisissez votre PIN');

    for (const digit of pin) {
        await page.click(`button:has-text("${digit}"):not(:has-text("Clear"))`);
    }

    // After the last digit the PinPad emits submit; expect redirect to
    // either shift-start (no open shift) or dashboard (resuming a shift).
    await page.waitForURL((url) => /\/pos-v2\/(shift\/start|dashboard)$/.test(url.pathname), { timeout: 5_000 });
}

export async function openShiftIfNeeded(page, initialCash = '100') {
    if (!page.url().includes('/shift/start')) return;
    // Type the cash amount via NumPad
    for (const c of initialCash) {
        if (c === '.') await page.click('button:has-text("."):not(:has-text("Clear"))');
        else await page.click(`button:has-text("${c}"):not(:has-text("Clear"))`);
    }
    await page.click('text=/Start shift|Ouvrir la caisse/');
    await page.waitForURL(/\/pos-v2\/dashboard$/);
}

export async function loginAndOpenShift(page) {
    await loginWithPin(page);
    await openShiftIfNeeded(page);
}
