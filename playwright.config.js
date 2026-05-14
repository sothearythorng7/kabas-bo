// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * POS V2 end-to-end test configuration.
 *
 * Activation:
 *   1. `npm i -D @playwright/test`
 *   2. `npx playwright install chromium`
 *   3. `npx playwright test`     (or `npm run e2e` once added to scripts)
 *
 * Target: `testing-bo.kabasconceptstore.com/pos-v2` behind HTTP basic auth.
 * The HTTP credentials are read from env vars (set in your shell or .env.test):
 *   KABAS_DEV_BASIC_USER=kabas-dev
 *   KABAS_DEV_BASIC_PASS=<ask mickaël>
 *
 * For local development, point E2E_BASE_URL at a Vite dev server instead.
 */
export default defineConfig({
    testDir: './tests-e2e/pos-v2',
    timeout: 30_000,
    expect: { timeout: 5_000 },
    fullyParallel: false, // The POS shares one shift at a time; serial avoids state collisions.
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [['list'], ['html', { open: 'never' }]],

    use: {
        baseURL: process.env.E2E_BASE_URL || 'https://testing-bo.kabasconceptstore.com',
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
        httpCredentials: process.env.KABAS_DEV_BASIC_USER ? {
            username: process.env.KABAS_DEV_BASIC_USER,
            password: process.env.KABAS_DEV_BASIC_PASS || '',
        } : undefined,
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        // Uncomment to validate iOS Safari tablet behaviour during UAT prep:
        // {
        //     name: 'ipad-safari',
        //     use: { ...devices['iPad Pro 11 landscape'] },
        // },
    ],
});
