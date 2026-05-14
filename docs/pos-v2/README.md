# POS V2 — Developer docs

Lives at `/pos-v2`, parallel to V1 (`/pos`). V1 is untouched and stays
the production path until Phase 10 cutover.

## Source layout

```
resources/js/pos-v2/
├── main.js, App.vue, router.js
├── api/
│   ├── client.js                 fetch wrapper (CSRF, 30s timeout, ApiError)
│   └── endpoints/                users, shifts, catalog, sales, voucher,
│                                  exchange, planning, events
├── stores/                       Pinia
│   ├── session, cart, sales, cash, catalog, journal, exchange,
│   ├── planning, i18n
├── composables/
│   ├── useSync.js                60s auto-sync loop + on-online retry
│   ├── useBarcodeScanner.js      HID burst detection
│   ├── useGiftCardCode.js        alphabet [A-HJ-NP-Z2-9]
│   ├── useCartCalculations.js    pure helpers
│   └── useReceiptPrinter.js      local daemon at :8888 + web fallback
├── components/                   shared UI building blocks
│   ├── PinPad, NumPad, TopBar, LeftRail
│   ├── catalog/                   CategoryBreadcrumb, CategoryChips,
│   │                              BrandFilter, ProductGrid, ProductCard
│   ├── cart/                      CartItem, HoldSalesDropdown, dialogs
│   ├── payment/                   PaymentDialog, VoucherInputDialog
│   ├── exchange/                  ExchangeWizard
│   ├── CashInOutDialog, ChangeUserDialog, ExpectedCashWidget
├── views/                        one per screen route
└── config/
    └── printer.js                URL, store config, ticket helpers
```

## Build

```
npm run build                     # both V1 and V2 bundles produced
```

V1 bundle hashes `app-BHbBGrvP.js`, `menu-Cjs6ZPtB.js`, `app-Bow6u6qL.css`
must remain identical across V2 commits — this is checked in CI as the
sanity invariant during Phase 9.

## Dev server (Vite HMR)

```
npm run dev
```

Apache serves the route `/pos-v2` from `resources/views/pos-v2/index.blade.php`
which `@vite('resources/js/pos-v2/main.js')`. With `npm run dev`, Vite
intercepts that asset and pushes HMR over WebSocket.

## API contract

The POS V2 talks to the same endpoints as V1 — see `docs/pos-v2/api-contract.md`
(or the V1 source of truth `routes/web.php` block under `Route::prefix('api/pos')`).

## E2E tests (Playwright)

The tests live in `tests-e2e/pos-v2/`. They target whatever URL is set in
`E2E_BASE_URL` (defaults to `testing-bo.kabasconceptstore.com`) with basic
auth credentials in `KABAS_DEV_BASIC_USER` / `KABAS_DEV_BASIC_PASS`.

```bash
# One-time setup (not committed because Playwright is ~200 MB of browsers)
npm i -D @playwright/test
npx playwright install chromium

# Run
KABAS_DEV_BASIC_USER=kabas-dev KABAS_DEV_BASIC_PASS=<ask> \
TEST_PIN=<seeded pos pin> \
TEST_EXCHANGEABLE_SALE_ID=<known sale id> \
npx playwright test
```

To skip the exchange test when no fixture sale is available, omit
`TEST_EXCHANGEABLE_SALE_ID`.

## Parity & UAT

- Manual parity checklist: [`parity-checklist.md`](./parity-checklist.md)
- Staff UAT scenarios:    [`uat-scenarios.md`](./uat-scenarios.md)

Tick every box in `parity-checklist.md` before scheduling Phase 10
(cutover). Staff UAT runs at least the 4 scenarios on real tablets with
a printer attached.

## Cutover (Phase 10)

The deliberate plan is:
1. Keep V1 reachable at `/pos` for at least 1 month after V2 goes live.
2. Add `POS_V2_ENABLED=true` to the BO `.env` of prod.
3. Update menu links in the BO to point to `/pos-v2` instead of `/pos`.
4. If anything breaks, flip the env flag back, links return to `/pos`,
   and no data is lost (both POS versions share the same `/api/pos/*`
   endpoints and the same DB).
